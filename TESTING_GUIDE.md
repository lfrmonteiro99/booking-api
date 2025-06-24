# 🧪 Integration Testing Guide - **WORKING SETUP!**

## ✅ Current Status
- **Authentication tests** - ✅ WORKING (8 tests, all passing)
- **Booking integration tests** - ✅ WORKING (8 tests, all passing)
- **Availability tests** - ✅ WORKING (10 tests, all passing) 
- **Database migrations** - ✅ WORKING 
- **Test configuration** - ✅ WORKING
- **End-to-end tests** - 🔧 Need refinement (rate limiting and setup issues)

## What Are Integration Tests?

Integration tests verify that different parts of your application work together correctly. Unlike unit tests that test individual functions, integration tests:

- Test **real HTTP requests** to your API endpoints
- Use a **real database** (SQLite in-memory for speed)
- Test the **complete flow**: Request → Controller → Service → Repository → Database → Response
- Verify **authentication and authorization** works
- Test **error handling** in realistic scenarios

## Test Structure Overview

```
tests/Feature/
├── IntegrationTestCase.php          # Base class with common setup
├── AuthIntegrationTest.php          # Authentication flow tests
├── BookingIntegrationTest.php       # Booking CRUD operations
├── AvailabilityIntegrationTest.php  # Availability checking & ingestion
└── EndToEndBookingFlowTest.php      # Complete user journeys
```

## How to Run Tests

### Run All Integration Tests
```bash
# In your project root
docker-compose exec app php artisan test --testsuite=Feature
```

### Run Specific Test File
```bash
# Run only booking tests
docker-compose exec app php artisan test tests/Feature/BookingIntegrationTest.php

# Run only authentication tests
docker-compose exec app php artisan test tests/Feature/AuthIntegrationTest.php
```

### Run Single Test Method
```bash
# Run specific test method
docker-compose exec app php artisan test --filter="it_creates_a_booking_with_pricing_calculation"
```

### Run Tests with Detailed Output
```bash
# Show detailed test output
docker-compose exec app php artisan test --testsuite=Feature -v
```

## Understanding Test Results

### ✅ Successful Test Output
```
PASS  Tests\Feature\BookingIntegrationTest
✓ it creates a booking with pricing calculation
✓ it retrieves user bookings
✓ it prevents accessing other users bookings

Tests:  3 passed
Time:   2.34s
```

### ❌ Failed Test Output
```
FAIL  Tests\Feature\BookingIntegrationTest
✓ it creates a booking with pricing calculation
✗ it retrieves user bookings

Expected status code 200 but received 500.
Failed asserting that 500 is identical to 200.

/app/tests/Feature/BookingIntegrationTest.php:67
```

## What Each Test File Does

### 1. AuthIntegrationTest.php
**Tests user authentication flows:**
- User registration with validation
- User login with credentials
- Accessing protected endpoints with tokens
- Logout functionality
- Invalid credential handling

**Example scenario**: User registers → gets token → accesses protected endpoint → logs out

### 2. BookingIntegrationTest.php  
**Tests booking CRUD operations:**
- Creating bookings with pricing calculation
- Retrieving user's bookings
- Getting specific booking details
- Authorization (users can't see others' bookings)
- Pricing preview functionality
- Validation errors

**Example scenario**: User creates booking → pricing is calculated → booking is stored → user can retrieve it

### 3. AvailabilityIntegrationTest.php
**Tests room availability system:**
- Checking room availability
- Filtering by guest count and dates
- Availability data ingestion
- Caching behavior
- Date range limits
- Multiple properties

**Example scenario**: Check availability → filter by guests → return available rooms with pricing

### 4. EndToEndBookingFlowTest.php
**Tests complete user journeys:**
- Full booking flow from registration to completion
- Authorization across multiple users
- Error handling throughout the process

**Example scenario**: Register → Login → Check availability → Get pricing → Create booking → View bookings → Logout

## How Tests Work Internally

### Test Database
- Uses **SQLite in-memory** database (super fast)
- **Fresh database** for each test method
- **Migrations run automatically** before each test
- **Test data created** in `setUp()` method

### Test Data Creation
```php
// Each test gets fresh data:
$this->testUser       // Authenticated user
$this->testProperty   // Test hotel property  
$this->testRoom       // Test room in the hotel
$this->authToken      // Valid authentication token
// + 7 days of availability data
```

### Authentication in Tests
```php
// Tests automatically include auth headers:
$response = $this->getJson('/api/bookings');
// Equivalent to:
$response = $this->getJson('/api/bookings', [
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json'
]);
```

## Reading Test Code

### Basic Test Structure
```php
/**
 * @test
 */
public function it_does_something()
{
    // ARRANGE: Set up test data
    $data = ['key' => 'value'];
    
    // ACT: Perform the action being tested
    $response = $this->postJson('/api/endpoint', $data);
    
    // ASSERT: Verify the results
    $response->assertStatus(200)
             ->assertJson(['success' => true]);
}
```

### Common Assertions
```php
// HTTP Status
$response->assertStatus(200);        // Success
$response->assertStatus(422);        // Validation error
$response->assertStatus(401);        // Unauthorized

// JSON Content
$response->assertJson(['key' => 'value']);                    // Exact match
$response->assertJsonStructure(['data' => ['id', 'name']]);   // Structure only
$response->assertJsonCount(5);                                // Array count

// Database
$this->assertDatabaseHas('bookings', ['user_id' => 1]);      // Record exists
$this->assertDatabaseMissing('bookings', ['id' => 999]);     // Record doesn't exist

// Validation Errors
$response->assertJsonValidationErrors(['email', 'password']); // Specific fields failed
```

## Debugging Failed Tests

### 1. Add Debug Output
```php
// In your test method:
$this->debugResponse($response);  // Prints full response
```

### 2. Check Specific Values
```php
// Print response data
dd($response->json());            // Dump and die
dump($response->getContent());    // Just dump
```

### 3. Check Database State
```php
// Check what's actually in database
$bookings = \App\Models\Booking::all();
dump($bookings->toArray());
```

### 4. Run Single Test for Focus
```bash
# Focus on one failing test
docker-compose exec app php artisan test --filter="it_creates_a_booking" -v
```

## Test Coverage Areas

### ✅ What We Test
- **API endpoints** work correctly
- **Authentication** and **authorization** 
- **Database operations** (create, read, update, delete)
- **Business logic** (pricing calculations, availability checks)
- **Error handling** (validation, not found, unauthorized)
- **Integration** between services
- **Real HTTP flows** from request to response

### ❌ What We Don't Test (in integration tests)
- Individual function logic (that's unit tests)
- External API calls (we mock those)
- UI behavior (that's frontend tests)
- Performance under load (that's load testing)

## Tips for Writing Good Integration Tests

### 1. Test Happy Path First
```php
// Start with the successful scenario
public function it_creates_booking_successfully()
{
    $response = $this->postJson('/api/bookings', $validData);
    $response->assertStatus(200);
}
```

### 2. Then Test Error Cases
```php
// Test what happens when things go wrong
public function it_rejects_invalid_booking_data()
{
    $response = $this->postJson('/api/bookings', $invalidData);
    $response->assertStatus(422);
}
```

### 3. Test Authorization
```php
// Always verify users can't access others' data
public function it_prevents_accessing_other_users_data()
{
    $otherUsersBooking = Booking::factory()->create(['user_id' => $otherUser->id]);
    $response = $this->getJson("/api/bookings/{$otherUsersBooking->id}");
    $response->assertStatus(404);
}
```

### 4. Use Descriptive Test Names
```php
// Good: Describes what and why
public function it_calculates_pricing_with_tax_for_multi_night_booking()

// Bad: Vague
public function test_booking()
```

## Common Issues and Solutions

### Issue: Tests Pass Individually but Fail in Suite
**Cause**: Database state bleeding between tests
**Solution**: Ensure `RefreshDatabase` trait is used and `setUp()` is called

### Issue: Authentication Errors
**Cause**: Token not included in request
**Solution**: Use `$this->getJson()` instead of `$this->json('GET')`

### Issue: Database Errors
**Cause**: Missing migrations or factories
**Solution**: Check factories exist and migrations run in `setUp()`

### Issue: Validation Errors
**Cause**: Test data doesn't match validation rules
**Solution**: Check FormRequest validation rules match test data

## Best Practices

1. **Keep tests independent** - each test should work alone
2. **Use factories** for creating test data consistently  
3. **Test error cases** not just happy paths
4. **Use descriptive names** that explain the scenario
5. **Group related tests** in the same test class
6. **Mock external services** (email, payment APIs)
7. **Test authorization** thoroughly for security
8. **Keep tests fast** with in-memory database

## Running Tests in CI/CD

```bash
# Example GitHub Actions command
docker-compose exec -T app php artisan test --testsuite=Feature --coverage
```

These integration tests ensure your booking API works correctly end-to-end and will catch issues before they reach production! 🚀