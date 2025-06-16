# Booking Availability API

This repository contains a Laravel 12 backend for a Booking Availability API, integrated with Docker for consistent development. It also features integration with Dialogflow for natural language processing of availability queries.

## Table of Contents
- [Requirements](#requirements)
- [Installation and Running](#installation-and-running)
- [Project Architecture & Strategies](#project-architecture--strategies)
- [Key Workflows](#key-workflows)
- [Testing](#testing)
- [API Endpoints](#api-endpoints)
- [Environment Variables](#environment-variables)
- [Contributing](#contributing)
- [API Documentation (Swagger)](#api-documentation-swagger)

## Requirements

Before you begin, ensure you have the following installed:

*   **Git**: For cloning the repository.
*   **Docker Desktop**: Includes Docker Engine and Docker Compose. Essential for running the application in a containerized environment.
*   **PHP (Optional, for Composer locally)**: While PHP is run inside Docker, Composer might be needed locally to run `composer install` if you prefer to manage dependencies outside the container initially (though `docker-compose run --rm app composer install` is recommended).
*   **Composer**: PHP dependency manager.
*   **Ngrok**: For exposing your local Laravel API to the internet, essential for Dialogflow webhook integration.

## Installation and Running

Follow these steps to get the project up and running:

1.  **Clone the repository:**
    ```bash
    git clone <your-repository-url>
    cd booking
    ```

2.  **Set up Environment Variables:**
    Paste env provided or create env file and copy relevant information on env file provided

3.  **Build and Run Docker Containers:**
    This will build the Docker images and start the `app`, `db`, and `redis` services. `--build --no-cache` ensures a clean build, picking up any Dockerfile changes (like Redis extension installation).
    ```bash
    docker-compose up -d --build
    ```

4.  **Install PHP Dependencies (shoulnd't be necessary, the Dockerfile already runs this):**
    ```bash
    docker-compose exec app composer install
    ```

5.  **Generate Application Key:**
    ```bash
    docker-compose exec app php artisan key:generate
    ```

6.  **Run Database Migrations and Seeders:**
    ```bash
    docker-compose exec app php artisan migrate
    ```

7.  **Run Laravel Server (inside Docker):**
    This will start the Laravel development server, typically on port `8080` (or as configured in your `Dockerfile`).
    ```bash
    docker-compose exec app php artisan serve --host=0.0.0.0 --port=8080
    ```
    Your Laravel API should now be accessible at `http://localhost:8080`.

8.  **Ngrok Setup and Run (for Dialogflow Webhook):**
    Dialogflow requires a publicly accessible URL for its webhook. Ngrok provides this.
    *   **Install Ngrok**: Follow instructions on [ngrok.com](https://ngrok.com/download).
    *   **Authenticate Ngrok**: You'll need an authtoken from your ngrok dashboard after signing up.
        ```bash
        ngrok config add-authtoken <your_ngrok_authtoken>
        ```
    *   **Run Ngrok**: In a *new terminal window*, expose your Laravel API's port (`8080`):
        ```bash
        ngrok http 8080
        ```
        Ngrok will provide a public URL (e.g., `https://xxxxxx.ngrok-free.app`). **Copy this URL.**

9.  **Configure Dialogflow Webhook:**
    *   Go to your Dialogflow ES Console.
    *   Navigate to **Fulfillment** in the left sidebar.
    *   Enable Webhook.
    *   Set **URL** to your Ngrok public URL + the webhook path: `https://xxxxxx.ngrok-free.app/api/dialogflow/webhook`
    *   In the "HEADERS" section, click "Add header".
        *   **Header Name**: `Authorization`
        *   **Value**: 'Bearer ' + The `DIALOGFLOW_WEBHOOK_SECRET` you set in your `.env` file.
    *   Save your Fulfillment settings.

## Project Architecture & Strategies

This project is built using Laravel, following several architectural patterns and strategies to ensure maintainability, scalability, and clear separation of concerns:

*   **Dockerization**: The entire development environment (PHP, MySQL, Redis) is containerized using Docker Compose. This ensures a consistent setup across different development machines and simplifies dependency management.
*   **Laravel Sanctum for API Authentication**: API authentication for the frontend application is handled via Laravel Sanctum, providing a token-based authentication system suitable for SPAs and mobile applications. A dedicated API token can be generated for general access (e.g., for testing without client-side login).
*   **Service Layer**: Business logic is encapsulated within dedicated Service classes (e.g., `AvailabilityService`, `DialogflowService`). This keeps controllers lean and focused on handling HTTP requests and responses.
*   **Repository Pattern**: Database interactions are abstracted through a Repository layer (`AvailabilityRepository`). This decouples the service layer from the ORM (Eloquent), making the application more flexible and testable.
*   **Laravel Queue/Jobs**: Long-running or resource-intensive tasks, such as bulk availability ingestion, are processed asynchronously using Laravel Queues and Jobs (`ProcessAvailabilityChunk`). This prevents HTTP request timeouts and improves user experience.
*   **Strategy Pattern for Dialogflow Intents**: The `DialogflowController` uses a Strategy pattern to handle different Dialogflow intents. Each intent has a dedicated handler class (`CheckAvailabilityIntentHandler`), which keeps the controller clean and makes it easy to add new intents without modifying existing logic.
*   **Dedicated API Token for General Access**: For scenarios requiring API access without a full client-side register/login flow (e.g., testing or specific integrations), a dedicated, non-expiring API token is created via a database migration. This allows direct authentication using a Bearer token.
*   **Caching Strategy**: Laravel's caching mechanism (backed by Redis) is strategically used to store frequently accessed availability data. 
    *   **Effectiveness**: By caching availability responses for specific property IDs, date ranges, and guest counts, the system avoids redundant, expensive database queries for repeated requests. 
    *   **Performance**: Subsequent requests for cached data are served directly from fast in-memory Redis, significantly reducing response times. 
    *   **Scalability**: Offloading read operations from the database to Redis reduces database load, allowing the application to handle a much higher volume of availability checks without performance degradation, thus improving scalability. Cache invalidation is managed by flushing relevant cache tags when new availability data is ingested.
*   **Rate Limiting**: API endpoints, particularly those exposed to external services or public access, are protected using Laravel's built-in rate limiting (`throttle:api`). 
    *   **Effectiveness**: This prevents abuse, denial-of-service (DoS) attacks, and ensures fair usage by limiting the number of requests a user or IP address can make within a given time frame. 
    *   **Performance**: It protects backend resources from being overwhelmed by excessive requests, maintaining the stability and responsiveness of the API. 
    *   **Scalability**: By shedding excessive load at the API gateway level, rate limiting helps maintain the performance of the core services under high traffic, contributing to overall system stability and scalability.

*   **Caching Details (Setup and Flushing)**:
    *   **How it's set**: Availability responses are cached in `app/Services/AvailabilityService.php` using `Cache::tags([...])->remember(...)`. A unique `cacheKey` is generated based on `property_id`, `check_in`, `check_out`, and `guests`. The data is stored in Redis, configured via `REDIS_HOST` and `REDIS_PORT` in `.env` and `config/database.php`.
    *   **Cache Tags**: The project utilizes cache tags (e.g., `'availability_property:' . $data['property_id']`) to logically group cached items.
        *   **Why Tags?**: Cache tags are crucial for selective invalidation. When new availability data is ingested or updated for a specific property, only the cache entries associated with that `property_id` are flushed. This prevents the need to clear the entire availability cache, ensuring that other property data remains cached and maximizing cache hit rates.
    *   **How it's flushed**: When new availability data is processed by the `ProcessAvailabilityChunk` job in `app/Jobs/ProcessAvailabilityChunk.php`, the following line is executed:
        ```php
        Cache::tags(['availability_property:' . $this->propertyId])->flush();
        ```
        This command specifically flushes all cache entries that were tagged with the given `propertyId`, ensuring that subsequent requests for that property's availability fetch the freshest data from the database.

## Key Workflows

### 1. Availability Ingestion

This workflow allows for bulk uploading of room availability data:

*   An external system (or Postman request) sends a JSON payload to the `/api/availability/ingest` endpoint.
*   The `AvailabilityIngestionController` receives the data.
*   It validates the incoming data.
*   A `ProcessAvailabilityChunk` job is dispatched to the Laravel queue.
*   The job processes the data in the background, upserting `Property`, `Room`, and `Availability` records into the database.
*   Cache for the affected property is flushed to ensure data freshness.

### 2. Availability Check (Frontend Integration)

This describes how the Vue calendar fetches availability data:

*   The Vue component makes a request to the `/api/availability` endpoint.
*   The `AvailabilityController` processes the request and returns a JSON response.
*   The Vue component displays available rooms as events on the calendar.

### 3. Dialogflow Integration (Natural Language Query)

This workflow enables users to query availability using natural language:

*   A user sends a natural language query to Dialogflow (e.g., "Do you have any rooms available next weekend?").
*   Dialogflow processes the query and sends a webhook request to the `/api/dialogflow/webhook` endpoint.
*   The `DialogflowController` receives the request and uses the `DialogflowService` to extract parameters (e.g., `check_in`, `check_out`, `guests`).
*   The `AvailabilityService` is called to check availability based on these parameters.
*   A human-readable response (e.g., "Yes! We have X rooms...") is generated.
*   This response is sent back to Dialogflow as `fulfillmentText`, which Dialogflow then relays to the user.

## Testing
Run the tests using:
```bash
docker-compose exec app php artisan test
```
The project has comprehensive unit tests covering controllers, services, repositories, and request validation. All tests should pass (59/59, 130 assertions).

## API Endpoints
- `GET /api/availability`: Check room availability.
- `POST /api/availability/ingest`: Ingest bulk availability data.
- `POST /api/subscribe`: Upgrade subscription (requires authentication).
- `POST /api/dialogflow/webhook`: Dialogflow webhook endpoint.
- `POST /api/register`: Register a new user.
- `POST /api/login`: Log in and receive an API token.
- `GET /api/user`: Get the authenticated user's details (requires authentication).
- `POST /api/logout`: Log out the current user (requires authentication).
- `POST /api/dialog/detect`: Detect intent from a user message (requires authentication).

### Example JSON Payload for Ingestion
For `/api/availability/ingest`, send a JSON array of properties, each with rooms and their availabilities:

```json
[
  {
    "property_id": "property-123",
    "rooms": [
      {
        "room_id": "room-1",
        "max_guests": 2,
        "date": "2024-07-01",
        "price": 120.00
      },
      {
        "room_id": "room-1",
        "max_guests": 2,
        "date": "2024-07-02",
        "price": 120.00
      }
    ]
  }
]
```

## API Authentication
Most endpoints require authentication via a Bearer token (Laravel Sanctum). You can obtain a token by:
- Using the fixed token created by the migration (see migration file for the value).
- Registering a user via `/api/register` and then logging in via `/api/login` to receive a token.

Include the token in the `Authorization` header:
```
Authorization: Bearer <your_token>
```

## Environment Variables
- `DB_CONNECTION`: Database connection type (e.g., `mysql`).
- `DB_HOST`: Database host (e.g., `db`).
- `DB_PORT`: Database port (e.g., `3306`).
- `DB_DATABASE`: Database name (e.g., `laravel`).
- `DB_USERNAME`: Database username (e.g., `sail`).
- `DB_PASSWORD`: Database password (e.g., `password`).
- `REDIS_HOST`: Redis host (e.g., `redis`).
- `REDIS_PORT`: Redis port (e.g., `6379`).
- `DIALOGFLOW_WEBHOOK_SECRET`: Secret key for Dialogflow webhook security.

## Contributing
Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## API Documentation (Swagger)

This project uses Swagger for API documentation. You can view the interactive documentation by running the application and navigating to:

[http://localhost:8080/api/documentation](http://localhost:8080/api/documentation)

To regenerate the documentation after making changes to the annotations in the controllers, run the following command:
```bash
docker-compose exec app php artisan l5-swagger:generate
```
