# Cinema Booking API

A REST API for online cinema seat booking with real-time seat locking.

Built with **Laravel 13**, **Laravel Reverb** (WebSocket), and documented with **Swagger UI**.

---

## Requirements

- PHP 8.3+
- Composer

---

## Setup

**1. Clone and install**
```bash
git clone <your-repo-url>
cd cinema-booking-api
composer install
```

**2. Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

If port `8080` is already in use on your machine, change `REVERB_PORT` and `REVERB_SERVER_PORT` in `.env` to a free port.

**3. Run migrations and seed demo data**
```bash
php artisan migrate
php artisan db:seed
```

This creates:
- 5 movies with 10 showtimes
- 2 halls with 50 seats each (rows A–E, numbers 1–10)
- 8 food & beverage items

---

## Running the Application

Open **3 terminal tabs** and run one command per tab:

```bash
# Tab 1 — API server (default port 8000, use --port=XXXX if it's taken)
php artisan serve

# Tab 2 — WebSocket server (real-time seat updates)
php artisan reverb:start

# Tab 3 — Background scheduler (cleans expired seat locks every minute)
php artisan schedule:work
```

---

## API Documentation (Swagger UI)

Open your browser and go to:

```
http://127.0.0.1:8000/api/documentation
```

All endpoints are listed with descriptions and a **Try it out** button for testing.

> If you run the server on a different port, update the `OA\Server` URL in `app/Http/Controllers/Auth/AuthController.php` to match before generating the docs.

**Quick start in Swagger:**
1. Call `POST /api/auth/register` to create an account
2. Call `POST /api/auth/login` — copy the `token` from the response
3. Click **Authorize** (top right) and paste the token
4. You're authenticated — test any endpoint

---

## Booking Flow

```
GET  /api/movies                         → pick a movie
GET  /api/movies/{id}/showtimes          → pick a showtime
GET  /api/showtimes/{id}/seats           → see available seats
POST /api/showtimes/{id}/seats/lock      → lock your seats (5 min)
POST /api/bookings                       → confirm booking
POST /api/bookings/{id}/payment          → pay (mock)
GET  /api/bookings/{id}                  → view confirmation
```

---

## Real-time Seat Locking

The key feature of this API is **first-come-first-serve seat locking** with real-time updates.

**How it works:**

1. User A opens the seat map — all seats show as `available`
2. User A locks seat A3 → `POST /api/showtimes/1/seats/lock`
3. The API saves the lock to the database with a 5-minute expiry
4. A `SeatStatusChanged` event is broadcast via **Laravel Reverb** (WebSocket)
5. All clients connected to channel `showtime.1` receive the event instantly
6. User B's seat map updates — A3 shows as `locked` without refreshing
7. If User B tries to lock A3, they receive `409 Conflict`

**If User A abandons the booking:**
- After 5 minutes, the lock expires automatically
- A background command (`php artisan schedule:work`) runs every minute
- It deletes expired locks and broadcasts that the seat is `available` again

**Why WebSocket over polling:**

| | Polling | WebSocket |
|--|---------|-----------|
| How it works | Client asks server every few seconds | Server pushes updates instantly |
| For 50 users on same showtime | 50 requests every 3 seconds | 1 broadcast reaches all 50 |
| Latency | Up to 3 seconds delay | Instant |

**Frontend example (Laravel Echo):**
```javascript
window.Echo.channel('showtime.1')
    .listen('.seat.status.changed', (data) => {
        // data = { showtimeId, seatId, row, number, status }
        console.log(`Seat ${data.row}${data.number} is now ${data.status}`);
    });
```

---

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | ❌ | Register |
| POST | `/api/auth/login` | ❌ | Login, get token |
| POST | `/api/auth/logout` | ✅ | Logout |
| GET | `/api/movies` | ❌ | List movies (supports `?search=`) |
| GET | `/api/movies/{id}` | ❌ | Movie detail |
| GET | `/api/movies/{id}/showtimes` | ❌ | Showtimes for a movie |
| GET | `/api/showtimes/{id}/seats` | ✅  | Seat map with live status |
| POST | `/api/showtimes/{id}/seats/lock` | ✅ | Lock seats (5 min) |
| DELETE | `/api/showtimes/{id}/seats/lock` | ✅ | Release seat locks |
| GET | `/api/fnb` | ❌ | Food & beverage list |
| POST | `/api/bookings` | ✅ | Create booking |
| GET | `/api/bookings/{id}` | ✅ | Booking detail |
| GET | `/api/payment-methods` | ❌ | List payment methods |
| POST | `/api/bookings/{id}/payment` | ✅ | Pay (mock) |

✅ = Bearer token required &nbsp; ❌ = Public
