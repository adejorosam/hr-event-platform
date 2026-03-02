# Event-Driven Multi-Country HR Platform

A real-time, event-driven backend platform built with Laravel, demonstrating microservice architecture with RabbitMQ messaging, Redis caching, WebSocket broadcasting, and server-driven UI patterns.

## Overview

### System Description

This platform consists of two Laravel services that work together to manage multi-country employee data:

- **HR Service** — A microservice that manages employee CRUD operations and publishes domain events
- **HubService** — The central orchestration layer that consumes events, validates data, caches intelligently, and serves dynamic APIs with real-time updates

### Technology Stack

| Component | Technology | Justification |
|-----------|------------|---------------|
| Framework | Laravel 11 | Mature ecosystem, built-in support for events, caching, broadcasting |
| Database | PostgreSQL 16 | Robust relational DB, excellent for structured employee data |
| Message Queue | RabbitMQ 3.13 | Industry-standard AMQP broker, topic exchange for flexible routing |
| Cache | Redis 7 | Sub-millisecond reads, pattern-based key deletion, native Laravel driver |
| WebSocket | Soketi 1.6 | Self-hosted Pusher-compatible server, zero external dependencies |
| Containerization | Docker Compose | Single-command orchestration of all 6 services |

### Design Decisions & Trade-offs

1. **Single employees table with nullable country-specific columns** — Chosen over EAV (Entity-Attribute-Value) or JSON columns for simplicity and queryability. Trade-off: nullable columns for unused fields per country, but this is negligible with only 2 countries and keeps queries simple.

2. **Strategy pattern for country logic** — `CountryChecklistInterface` implementations (USAChecklist, GermanyChecklist) make adding new countries as simple as creating a new class and registering it. No existing code needs modification (Open/Closed Principle).

3. **Topic exchange in RabbitMQ** — Routing keys like `employee.created.usa` allow the HubService to subscribe to all employee events with `employee.#`, while still enabling future consumers to subscribe to country-specific events only.

4. **Redis for caching with event-driven invalidation** — Cache-aside pattern where the HubService checks cache first, fetches from HR Service on miss. Cache is invalidated when RabbitMQ events arrive, ensuring eventual consistency with minimal latency.

5. **Soketi over Pusher** — Self-hosted to demonstrate full control over the WebSocket infrastructure. Pusher-compatible API means the same Laravel broadcasting code works with either option.

6. **Observer pattern for event publishing** — The HR Service uses Eloquent Observers to automatically publish events on model changes, keeping controllers clean and ensuring events are never missed.

## Architecture

### System Architecture Diagram

```
┌─────────────┐     events      ┌─────────────┐     events      ┌─────────────────┐
│  HR Service │ ──────────────► │  RabbitMQ   │ ──────────────► │   HubService    │
│  (Port 8001)│                 │ (Port 5672) │                 │  (Port 8002)    │
│             │                 │ UI: 15672   │                 │                 │
│ Employee    │                 │             │                 │ Event Processor │
│ CRUD API    │                 │ Topic       │                 │ Cache Layer     │
│ + Observer  │                 │ Exchange    │                 │ Checklist Engine│
└─────────────┘                 └─────────────┘                 │ REST APIs       │
                                                                 └────────┬────────┘
                                                                          │
                                                           ┌──────────────┼──────────────┐
                                                           │              │              │
                                                    ┌──────▼──────┐ ┌────▼────┐  ┌──────▼──────┐
                                                    │   Redis     │ │ Soketi  │  │ PostgreSQL  │
                                                    │ (Port 6379) │ │ (6001)  │  │ (Port 5432) │
                                                    │ Cache Store │ │ WS      │  │ Database    │
                                                    └─────────────┘ └────┬────┘  └─────────────┘
                                                                         │
                                                                    ┌────▼────┐
                                                                    │ Browser │
                                                                    │ Clients │
                                                                    └─────────┘
```

### Data Flow

1. **Employee Created/Updated/Deleted** via HR Service REST API
2. **Eloquent Observer** publishes event to RabbitMQ topic exchange with routing key `employee.{action}.{country}`
3. **HubService consumer** (`rabbitmq:consume` command) receives the event from the queue
4. **Event Processor** (strategy pattern) handles the event:
   - Updates/invalidates Redis cache
   - Broadcasts WebSocket event via Soketi
5. **Connected clients** receive real-time updates on subscribed channels
6. **API endpoints** serve cached data, falling back to HR Service on cache miss

### Cache Key Structure

```
employees:{country}:list:{page}:{perPage}  → Paginated employee lists (TTL: 1 hour)
employees:{country}:{id}                    → Individual employee data (TTL: 1 hour)
checklists:{country}                        → Aggregated checklist data (TTL: 30 min)
```

### WebSocket Channel Strategy

```
country.{country}                 → Country-level updates (employee list changes)
checklist.{country}               → Checklist data updates
employee.{country}.{employee_id}  → Individual employee updates
```

## Getting Started

### Prerequisites

- Docker Desktop
- Docker Compose

### Repository

This project is hosted on GitHub: [https://github.com/adejorosam/hr-event-platform](https://github.com/adejorosam/hr-event-platform)
### Quick Start

```bash
# Clone the repository
git clone https://github.com/adejorosam/hr-event-platform.git
cd hr-event-platform

# Start all services
docker-compose up -d

# Wait for services to be ready (check logs)
docker-compose logs -f hr-service hub-service
```

The system starts with a single command: `docker-compose up -d`

### Service URLs

| Service | URL |
|---------|-----|
| HR Service API | http://localhost:8001/api |
| HubService API | http://localhost:8002/api |
| RabbitMQ Management | http://localhost:15672 (guest/guest) |
| WebSocket Test Page | Open `websocket-test/index.html` in browser |

## API Documentation

### HR Service (Port 8001)

#### Create Employee
```bash
# USA Employee
curl -X POST http://localhost:8001/api/employees \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John",
    "last_name": "Doe",
    "salary": 75000,
    "ssn": "123-45-6789",
    "address": "123 Main St, New York, NY",
    "country": "USA"
  }'

# Germany Employee
curl -X POST http://localhost:8001/api/employees \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Hans",
    "last_name": "Mueller",
    "salary": 65000,
    "goal": "Increase team productivity by 20%",
    "tax_id": "DE123456789",
    "country": "Germany"
  }'
```

#### List, Show, Update, Delete
```bash
GET    /api/employees              # List all (with ?country= filter)
GET    /api/employees/{id}         # Show one
PUT    /api/employees/{id}         # Update
DELETE /api/employees/{id}         # Delete
```

### HubService (Port 8002)

#### Checklists
```bash
GET /api/checklists?country=USA
GET /api/checklists?country=Germany
```

#### Steps (Navigation)
```bash
GET /api/steps?country=USA        # Returns: Dashboard, Employees
GET /api/steps?country=Germany    # Returns: Dashboard, Employees, Documentation
```

#### Employees (Server-Driven Columns)
```bash
GET /api/employees?country=USA&page=1&per_page=15
GET /api/employees?country=Germany
```

#### Schema (Widget Configuration)
```bash
GET /api/schema/dashboard?country=USA
GET /api/schema/dashboard?country=Germany
GET /api/schema/employees?country=USA
```

## Testing

```bash
# HR Service tests
docker-compose exec hr-service php artisan test

# HubService tests
docker-compose exec hub-service php artisan test

# Run specific test suite
docker-compose exec hub-service php artisan test --testsuite=Unit
docker-compose exec hub-service php artisan test --testsuite=Feature
docker-compose exec hub-service php artisan test --testsuite=Integration
```
