# Foodies Phase 1 API Contract

Base URL during local development:

```text
http://127.0.0.1:8000/api
```

All Phase 1 endpoints return JSON with a top-level `data` key.

## GET `/public/settings`

Returns restaurant/app identity and theme settings.

```json
{
  "data": {
    "id": 1,
    "restaurant_name": "Foodies Kitchen",
    "app_name": "Foodies",
    "tagline": "Fresh food, fast taps",
    "logo_path": null,
    "app_icon_path": null,
    "phone": "+92 300 0000000",
    "email": "hello@foodies.test",
    "whatsapp": "+92 300 0000000",
    "address": "Main Road, Sargodha",
    "about_text": "A single-restaurant ordering app for fresh meals, pickup, and delivery.",
    "primary_color": "#FFC107",
    "secondary_color": "#111111",
    "background_color": "#F8F2E9",
    "button_color": "#FFC107",
    "is_open": true
  }
}
```

## GET `/public/home`

Returns dynamic home screen copy.

```json
{
  "data": {
    "id": 1,
    "hero_title": "What would you like to eat today?",
    "hero_subtitle": "Browse the menu, add favorites, and keep your cart ready.",
    "search_placeholder": "Search biryani, pizza, drinks...",
    "popular_section_title": "Popular Items",
    "category_section_title": "Categories",
    "announcement_text": "Fresh deals are available today."
  }
}
```

## GET `/public/banners`

Returns active banners sorted by `sort_order`.

```json
{
  "data": [
    {
      "id": 1,
      "title": "Fresh meals made today",
      "subtitle": "Hot favorites ready for pickup or delivery.",
      "image_path": null,
      "button_text": null,
      "button_link": null,
      "sort_order": 1
    }
  ]
}
```

## GET `/public/categories`

Returns active menu categories.

```json
{
  "data": [
    {
      "id": 1,
      "name": "Burgers",
      "slug": "burgers",
      "icon": "B",
      "image_path": null,
      "sort_order": 1,
      "menu_items_count": 2
    }
  ]
}
```

## GET `/public/menu-items`

Returns available menu items with category data.

```json
{
  "data": [
    {
      "id": 5,
      "category_id": 3,
      "name": "Chicken Biryani",
      "slug": "chicken-biryani",
      "description": "Fragrant rice layered with tender chicken and spices.",
      "price": "5.50",
      "discount_price": null,
      "image_path": null,
      "is_available": true,
      "is_popular": true,
      "stock_quantity": null,
      "preparation_time": 12,
      "sort_order": 5,
      "category": {
        "id": 3,
        "name": "Biryani",
        "slug": "biryani",
        "icon": "B",
        "image_path": null,
        "sort_order": 3
      }
    }
  ]
}
```

## GET `/public/menu-items/{slug}`

Returns one available menu item by slug.

Example:

```text
GET /public/menu-items/chicken-biryani
```
