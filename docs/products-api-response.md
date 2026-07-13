# Products API — Response Reference

Base path: `/api/products`

## Endpoints

| Method | Path                  | Auth | Description               |
|--------|-----------------------|------|----------------------------|
| GET    | `/api/products`       | No   | Paginated list of products |
| GET    | `/api/products/{id}`  | No   | Single product             |
| POST   | `/api/products`       | Yes  | Create a product           |
| PUT    | `/api/products/{id}`  | Yes  | Update a product           |
| DELETE | `/api/products/{id}`  | Yes  | Delete a product           |

`Yes` auth means the request needs a Bearer token (`auth:api`) from `/api/auth/login`.

## Query parameters (`GET /api/products`)

| Param         | Type    | Description                                   |
|---------------|---------|------------------------------------------------|
| `is_active`   | boolean | Filter by active/inactive                     |
| `category_id` | int     | Filter by category                            |
| `search`      | string  | Matches against `title` (partial, case-sensitive `LIKE`) |
| `per_page`    | int     | Page size (default `15`)                      |

## Response shape

### `GET /api/products` (list)

```json
{
  "data": [ /* array of Product objects, see below */ ],
  "current_page": 1,
  "per_page": 15,
  "total": 42,
  "last_page": 3
}
```

### `GET /api/products/{id}` (single)

Returns a single **Product object** directly (not wrapped in `data`).

## Product object

```json
{
  "id": 5,
  "title": "Galileo Lens",
  "description": "The Galileo system is ideal for dentists and students...",
  "image": "https://pub-xxxxxxxx.r2.dev/products/01KXBZ9PY774A5NSSYM4JVEYT5.JPG",
  "category_id": 5,
  "is_active": true,
  "created_at": "2026-07-12T20:11:49.000000Z",
  "updated_at": "2026-07-12T20:11:49.000000Z",
  "category": { /* Category object, see below */ },
  "colors": [ /* array of Color objects, see below */ ],
  "models": [ /* array of Model objects, see below */ ],
  "images": [ /* array of Image objects, see below */ ]
}
```

| Field         | Type          | Notes                                                                 |
|---------------|---------------|------------------------------------------------------------------------|
| `id`          | int           |                                                                          |
| `title`       | string        |                                                                          |
| `description` | string\|null  |                                                                          |
| `image`       | string\|null  | Full public URL (resolved from R2), not a raw storage path             |
| `category_id` | int\|null     |                                                                          |
| `is_active`   | bool          |                                                                          |
| `created_at`  | ISO 8601 string |                                                                        |
| `updated_at`  | ISO 8601 string |                                                                        |
| `category`    | object\|null  | Present when the product has a category, always eager-loaded          |
| `colors`      | array         | Always present (empty array if none attached)                          |
| `models`      | array         | Always present (empty array if none created)                           |
| `images`      | array         | Always present (empty array if none uploaded); ordered by `sort_order` |

> Note: `price`, `price_after_discount`, and `attributes` columns still exist on the `products` table for legacy reasons but are no longer used — pricing and attributes now live per **model** (see below). Don't rely on them in new integrations.

## Category object (nested)

```json
{
  "id": 5,
  "name": "Adjustable PD",
  "slug": "adjustable-pd",
  "description": null,
  "is_active": true,
  "parent_id": null,
  "image": "https://pub-xxxxxxxx.r2.dev/categories/xxxx.jpg",
  "sort_order": 3,
  "created_at": "2026-07-12T18:53:24.000000Z",
  "updated_at": "2026-07-12T18:53:24.000000Z"
}
```

`image` is `null` if the category has no image.

## Color object (nested, per product)

```json
{
  "id": 1,
  "name": "white",
  "hex_code": "#FFFFFF",
  "is_active": true,
  "created_at": "2026-06-01T12:26:10.000000Z",
  "updated_at": "2026-06-01T12:26:10.000000Z",
  "pivot": {
    "product_id": 5,
    "color_id": 1,
    "image": null
  },
  "image": "https://pub-xxxxxxxx.r2.dev/product-colors/xxxx.jpg"
}
```

| Field              | Type         | Notes                                                                 |
|--------------------|--------------|--------------------------------------------------------------------|
| `pivot.image`      | string\|null | Raw storage path as stored in the `product_color` pivot table       |
| `image` (top-level)| string\|null | Convenience field — the resolved full URL for **this product's** color image. Prefer this over `pivot.image`. |

Each color is specific to the product it's attached to — the same `Color` can have a different image on different products.

## Model object (nested, per product)

A "model" represents a variant of the product (e.g. a specific magnification/size) with its own price and attributes.

```json
{
  "id": 1,
  "product_id": 5,
  "name": "SF 2.5",
  "price": "2000.00",
  "price_after_discount": "1000.00",
  "image": "https://pub-xxxxxxxx.r2.dev/product-models/xxxx.jpg",
  "attributes": {
    "Magnification": "2.5X",
    "Working Distance": "300-580mm",
    "Pupillary Distance Adjustment": "50-80mm",
    "Depth Of Field": "200mm",
    "Field Of View": "150-170mm",
    "Weight": "69g"
  },
  "is_active": true,
  "created_at": "2026-07-12T20:11:49.000000Z",
  "updated_at": "2026-07-12T20:11:49.000000Z"
}
```

| Field                   | Type            | Notes                                              |
|-------------------------|-----------------|------------------------------------------------------|
| `price`                 | string\|null    | Decimal as string, 2 places                          |
| `price_after_discount`  | string\|null    | Decimal as string, 2 places                          |
| `image`                 | string\|null    | Full resolved URL                                    |
| `attributes`            | object\|null    | Free-form key/value pairs, defined per model in the admin panel |
| `is_active`             | bool            |                                                       |

A product with no models returns `"models": []`.

## Image object (nested, per product — gallery)

The product's main `image` field (top-level) is a single cover/thumbnail image. `images` is a separate gallery — additional photos shown e.g. in a product detail carousel.

```json
{
  "id": 1,
  "product_id": 5,
  "image": "https://pub-xxxxxxxx.r2.dev/product-images/xxxx.jpg",
  "sort_order": 0,
  "created_at": "2026-07-13T10:34:46.000000Z",
  "updated_at": "2026-07-13T10:34:46.000000Z"
}
```

| Field        | Type   | Notes                                                        |
|--------------|--------|---------------------------------------------------------------|
| `image`      | string | Full resolved URL                                              |
| `sort_order` | int    | Display order (ascending); managed via drag-reorder in the admin panel |

A product with no gallery images returns `"images": []`. There is currently no public write endpoint for gallery images — they're managed inline on the product form in the admin panel only.

## Notes for frontend integration

- All `image` fields returned by this API are already resolved to absolute, publicly-accessible URLs — never build the R2 URL yourself from a raw path.
- `colors[].image`, `models[].image`, and the top-level `image` can be `null` if no image was uploaded for that color/model/product.
- `models` and `images` can be empty — always guard against `models.length === 0` / `images.length === 0` when a product has no variants or gallery photos yet.

## Writing products (`POST` / `PUT`)

Both endpoints require a Bearer token and accept `multipart/form-data` (needed for the `image` file upload). For `PUT`, Laravel's usual method-spoofing applies if you're sending via a form (`_method=PUT`).

### Fields

| Field         | Type              | Required (`POST`) | Required (`PUT`) | Notes                                      |
|---------------|-------------------|--------------------|-------------------|---------------------------------------------|
| `title`       | string, max 255   | yes                | no (`sometimes`)  |                                               |
| `description` | string            | no                 | no                | nullable                                     |
| `image`       | file (image, ≤5MB)| no                 | no                | uploaded to R2 under `products/`             |
| `category_id` | int               | no                 | no                | must exist in `categories`                   |
| `is_active`   | boolean           | no                 | no                |                                               |
| `colors`      | array of int      | no                 | no                | color IDs to attach (see caveat below)       |
| `colors.*`    | int               | no                 | no                | must exist in `colors`                       |

### Example request (`POST /api/products`)

```
POST /api/products
Authorization: Bearer {token}
Content-Type: multipart/form-data

title=Galileo Lens
description=A wide field of view loupe
category_id=5
is_active=1
image=@lens.jpg
colors[]=1
colors[]=2
```

Response: `201 Created` with the full Product object (same shape as `GET /api/products/{id}`).

### Important caveats

- **This endpoint does not manage models.** `POST`/`PUT /api/products` only creates/updates the product record itself and its `colors` attachment. Models (variants with name/price/attributes/image) must be created separately via `/api/product-models` (see below), or through the admin panel where they're managed inline on the product form.
- **`colors` here does not support per-color images.** Sending `colors` as a plain array of IDs calls `sync()`, which resets each color's pivot `image` to `null`. If a product's colors already have images (set via the admin panel), submitting `colors` through this API **will wipe those images**. If you need to set/update a color's image for a product, do it through the admin panel for now — there's no public endpoint for it yet.

### Managing models (`/api/product-models`)

| Method | Path                        | Auth | Description         |
|--------|-----------------------------|------|----------------------|
| GET    | `/api/product-models`       | No   | List models (filterable by `product_id`, `is_active`) |
| GET    | `/api/product-models/{id}`  | No   | Single model          |
| POST   | `/api/product-models`       | Yes  | Create a model        |
| PUT    | `/api/product-models/{id}`  | Yes  | Update a model        |
| DELETE | `/api/product-models/{id}`  | Yes  | Delete a model        |

Body fields for `POST`/`PUT`: `product_id` (required on create), `name`, `price`, `price_after_discount`, `image` (file), `is_active`, `attributes` (array). Response shape matches the Model object documented above.
