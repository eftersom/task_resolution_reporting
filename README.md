# Task Resolution Reporting

This is a Laravel reporting API that focuses on work task resolution types (currently.) Work tasks are linked to calls in a one-to-one relationship and work tasks are also linked to resolution types, which outlines how the task has been or is in the process of being resolved. The singular reporting endpoint we have included here returns resolution types with counts of associated work tasks excluding any tasks linked to calls that have been archived or are in the draft stage, filtered by date ranges to and from. 

For this exercise I have decided to ensure that the end user has limits on how many times they can access the end point within a specific timeframe (30 calls per minute maximum), this would for example prevent denial of service attacks or for example to prevent rogue AI agents calling the endpoint that are stuck in loops from accessing the API endlessly (a new common issue when external devs are working with endpoints). 

```
Route::prefix('reports')
    ->middleware('throttle:30,1')
    ->group(function () {
        Route::get('work-tasks/resolutions', [TaskReportingController::class, 'resolutionTypeSummary']);
    });
```

This could be setup using envs for example to increase or decrease limits without the need to deploy.

## Tech Stack in Use

- PHP 8.4 / Laravel 13
- MySQL 8.4
- Nginx and PHP-FPM using Supervisor
- Docker

## Getting Started

### Prerequisites

- Docker and Docker Compose

### To Run the Code

```
docker compose build
docker compose up -d
```

The app will then be available at `http://localhost:8080`.

### To Shut Everything Down

```
docker compose down
```

## API
### GET `/api/reports/work-tasks/resolutions`

This returns JSON detailing resolution types with work type counts within a specific date range.

**Query Parameters**

| Parameters  | Is Needed? | Format       | Description         |
|-------------|------------|--------------|---------------------|
| from        | Yes        | YYYY-MM-DD   | Start of date range |
| to          | Yes        | YYYY-MM-DD   | End of date range   |

**For example...**

```
GET /api/reports/work-tasks/resolutions?from=2026-01-01&to=2026-04-14
```

**Will provide something like...**

```json
{
  "data": [
    {
      "id": 1,
      "name": "In-progress - Awaiting Purchase Order from Customer",
      "description": "Awaiting purchase order from customer",
      "count": 5
    },
    {
      "id": 2,
      "name": "In-progress - Parts On Order",
      "description": "Replacement parts have been ordered",
      "count": 10
    }
  ],
  "meta": {
    "startDate": "2026-01-01",
    "endDate": "2026-04-14",
    "total_tasks": 15
  }
}
```

**Rules/Behaviours...**

- Any Tasks that are linked to calls with a stage of `draft` or `archived` will be excluded
- Any Tasks with no resolution type will also be excluded
- List of results are sorted by count descending, highest to lowest
- All responses are cached for 60 seconds which can be configured via the `REPORTING_CACHE_TTL` env variable
- Requests to the endpoint are rate limited to 30 requests per minute per user IP
- To prevent users from grabbing excessive amounts of data date ranges are capped at 366 days

**Explanations:**

**Transformers** -
For this project I have used resources I will call transformers. This might seem like a needless step to some but it is important to control what actually gets output to the end user in a consistent maintainable way. Seperating the output from the main bulk of the code allows us to transform any 'raw' output for example, into specific fields that will ultimately be provided by the endpoint, preventing any 'leakage' of sensitive data or unnecessary 'noise' and ensuring that developers actually consider every aspect of the expected output when making changes to any original functionality.

**Factories** -
For the sake of this task and for testing purposes, factories can preload useful data into the system.

**Services** -
I think that a service based architecture is useful for seperating controller logic from service logic. This is often utilised in MVC architectures for example and can ensure that controllers remain lean and unbloated. Placing the actual logic into services will eventually allow the system to grow more organically as more services are added to perform different functions. With each service used to cater to specific business logic for example, especially in the context of reporting. 

**Scoping Worktask Logic** -
This is useful because it plants firm work task logic in a central place. And ensures for example that work tasks with active calls are considered when loading work tasks (at least for this endpoint.)

**Form Requests** -
This is something many developers tend to overlook but catching 'bad input' before it enters the system is important and strict validation processes can matter a lot to a system that might end up receiving many requests in a short space of time. 

## Outline of Project Structure

```
app/
  Http/
    Controllers/Api/TaskReportingController.php    # Lean controller
    Requests/TaskResolutionReportRequest.php       # Validation & param guarding
  Models/
    Call.php                                       # Has one work task
    ResolutionType.php                             # Has many work tasks
    WorkTask.php                                   # Belongs to call & resolution type
  Services/TaskReportingService.php                # Query logic & caching
  Transformers/ResolutionTypeSummaryTransformer.php # Response shaping
config/reporting.php                               # Reporting configuration
routes/api.php                                     # API routes
```

## Project Configuration

Reporting settings can be found within `config/reporting.php`:

| Config Key         | Value                            | Description                         |
|--------------------|----------------------------------|-------------------------------------|
| cache_ttl          | 60 (env `REPORTING_CACHE_TTL`)   | Cache duration in seconds           |
| max_range_days     | 366                              | Max allowed date range span         |
| allowed_params     | ['from', 'to']                   | Accepted query parameters           |
| excluded_stages    | ['draft', 'archived']            | Call stages excluded from reporting |

## Database Connection (external clients)

| Field    | Value                        |
|----------|------------------------------|
| Host     | 127.0.0.1                    |
| Port     | 3307                         |
| Username | root                         |
| Password | secret                       |
| Database | task_resolution_reporting    |
