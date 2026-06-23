export type ApiResponse<T> = {
  data: T
}

export type RestaurantSettings = {
  id: number
  restaurant_name: string
  app_name: string
  tagline?: string | null
  logo_path?: string | null
  app_icon_path?: string | null
  phone?: string | null
  email?: string | null
  whatsapp?: string | null
  address?: string | null
  about_text?: string | null
  primary_color: string
  secondary_color: string
  background_color: string
  button_color: string
  is_open: boolean
}

export type HomeSection = {
  id: number
  hero_title: string
  hero_subtitle: string
  search_placeholder: string
  popular_section_title: string
  category_section_title: string
  announcement_text?: string | null
}

export type Banner = {
  id: number
  title: string
  subtitle?: string | null
  image_path?: string | null
  button_text?: string | null
  button_link?: string | null
  sort_order: number
}

export type Category = {
  id: number
  name: string
  slug: string
  icon?: string | null
  image_path?: string | null
  sort_order: number
  menu_items_count?: number
}

export type MenuItem = {
  id: number
  category_id: number
  name: string
  slug: string
  description?: string | null
  price: string | number
  discount_price?: string | number | null
  image_path?: string | null
  is_available: boolean
  is_popular: boolean
  stock_quantity?: number | null
  preparation_time?: number | null
  sort_order: number
  category?: Category
}
