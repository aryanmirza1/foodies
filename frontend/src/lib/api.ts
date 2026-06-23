import { useEffect, useState } from 'react'
import { API_BASE_URL } from './config'
import type { ApiResponse, Banner, Category, HomeSection, MenuItem, RestaurantSettings } from '../types/api'

async function apiGet<T>(path: string): Promise<T> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    headers: {
      Accept: 'application/json',
    },
  })

  if (!response.ok) {
    throw new Error(`API request failed: ${response.status}`)
  }

  const payload = (await response.json()) as ApiResponse<T>
  return payload.data
}

function useApi<T>(path: string, enabled = true) {
  const [data, setData] = useState<T | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(enabled)

  useEffect(() => {
    if (!enabled) {
      return
    }

    let mounted = true

    Promise.resolve()
      .then(() => {
        if (mounted) {
          setLoading(true)
        }

        return apiGet<T>(path)
      })
      .then((result) => {
        if (mounted) {
          setData(result)
          setError(null)
        }
      })
      .catch((caught: unknown) => {
        if (mounted) {
          setError(caught instanceof Error ? caught.message : 'API request failed')
        }
      })
      .finally(() => {
        if (mounted) {
          setLoading(false)
        }
      })

    return () => {
      mounted = false
    }
  }, [enabled, path])

  return { data, error, loading }
}

export function useSettings() {
  return useApi<RestaurantSettings>('/public/settings')
}

export function useHomeSection() {
  return useApi<HomeSection>('/public/home')
}

export function useBanners() {
  return useApi<Banner[]>('/public/banners')
}

export function useCategories() {
  return useApi<Category[]>('/public/categories')
}

export function useMenuItems() {
  return useApi<MenuItem[]>('/public/menu-items')
}

export function useMenuItem(slug: string | undefined) {
  return useApi<MenuItem>(`/public/menu-items/${slug ?? ''}`, Boolean(slug))
}
