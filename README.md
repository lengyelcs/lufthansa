# Lufthansa - Symfony with Docker

## Setup instructions

1. Clone this repository:
   ```
   git clone <repository-url>
   cd lufthansa
   ```

2. Start the Docker containers:
   ```
   docker-compose up --build

   -- or after build --

   docker-compose up -d
   ```

3. Access the application:
   - API Base URL: http://localhost/api/users | http://127.0.0.1/api/users
   - Database: localhost:3306
     - Username: symfony
     - Password: symfony
     - Database: symfony

## API access via curl

**Enter into the container**
```bash
docker exec -it lufthansa-task-php-1 bash
```

### Get user

1. Get all users (JSON format - default)
```bash
curl -X GET "http://nginx/api/users"
```
or
```bash
curl -X GET "http://nginx/api/users" -H "Accept: application/json"
```

2. Get all users (YAML format)
```bash
curl -X GET "http://nginx/api/users?format=yaml"
```

3. Get user by specific id (JSON format - default)
```bash
curl -X GET "http://nginx/api/users/1" -H "Accept: application/json"
```

4. Get user by specific id (YAML format)
```bash
curl -X GET "http://nginx/api/users/1?format=yaml"
```


### Create user

```bash
curl -X POST "http://nginx/api/users" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "firstName": "firstName1",
    "lastName": "lastName1",
    "email": "firstName1.lastName1@email.com",
    "password": "password123"
  }'
```
