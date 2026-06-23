import { useEffect, useMemo, useState } from 'react'
import type { MenuItem } from '../types/api'

export type CartItem = {
  id: number
  slug: string
  name: string
  price: number
  image_path?: string | null
  quantity: number
  note?: string
}

const CART_KEY = 'foodies.cart.v1'
const listeners = new Set<() => void>()

function readCart(): CartItem[] {
  try {
    const raw = localStorage.getItem(CART_KEY)
    return raw ? (JSON.parse(raw) as CartItem[]) : []
  } catch {
    return []
  }
}

function writeCart(items: CartItem[]) {
  localStorage.setItem(CART_KEY, JSON.stringify(items))
  listeners.forEach((listener) => listener())
}

function notifyFromStorage(event: StorageEvent) {
  if (event.key === CART_KEY) {
    listeners.forEach((listener) => listener())
  }
}

window.addEventListener('storage', notifyFromStorage)

export function addToCart(item: MenuItem, quantity = 1, note = '') {
  const items = readCart()
  const existing = items.find((cartItem) => cartItem.id === item.id)
  const price = Number(item.discount_price ?? item.price)

  if (existing) {
    existing.quantity += quantity
    existing.note = note || existing.note
  } else {
    items.push({
      id: item.id,
      image_path: item.image_path,
      name: item.name,
      note,
      price,
      quantity,
      slug: item.slug,
    })
  }

  writeCart(items)
}

export function setCartQuantity(id: number, quantity: number) {
  const next = readCart()
    .map((item) => (item.id === id ? { ...item, quantity } : item))
    .filter((item) => item.quantity > 0)

  writeCart(next)
}

export function removeFromCart(id: number) {
  writeCart(readCart().filter((item) => item.id !== id))
}

export function clearCart() {
  writeCart([])
}

export function useCart() {
  const [items, setItems] = useState<CartItem[]>(() => readCart())

  useEffect(() => {
    const listener = () => setItems(readCart())
    listeners.add(listener)

    return () => {
      listeners.delete(listener)
    }
  }, [])

  return useMemo(() => {
    const subtotal = items.reduce((sum, item) => sum + item.price * item.quantity, 0)
    const count = items.reduce((sum, item) => sum + item.quantity, 0)

    return {
      count,
      deliveryFee: 0,
      items,
      subtotal,
      total: subtotal,
    }
  }, [items])
}
