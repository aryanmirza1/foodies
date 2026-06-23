import { Plus, Star } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { MenuItem } from '../../types/api'
import { Button } from './Button'
import { Card } from './Card'

type FoodCardProps = {
  item: MenuItem
  onAdd: (item: MenuItem) => void
}

export function FoodCard({ item, onAdd }: FoodCardProps) {
  const price = Number(item.discount_price ?? item.price)
  const originalPrice = item.discount_price ? Number(item.price) : null

  return (
    <Card className="overflow-hidden">
      <Link className="block" to={`/menu/${item.slug}`}>
        <div className="relative h-36 bg-gradient-to-br from-amber-100 via-white to-stone-100">
          {item.image_path ? (
            <img alt={item.name} className="h-full w-full object-cover" src={item.image_path} />
          ) : (
            <div className="grid h-full place-items-center text-5xl font-black text-amber-400">{item.name.charAt(0)}</div>
          )}
          {item.is_popular ? (
            <span className="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-black text-stone-800 shadow-sm">
              <Star className="fill-amber-400 text-amber-400" size={13} />
              Popular
            </span>
          ) : null}
        </div>
      </Link>
      <div className="p-4">
        <Link to={`/menu/${item.slug}`}>
          <h3 className="line-clamp-1 text-lg font-black">{item.name}</h3>
          <p className="mt-1 line-clamp-2 min-h-10 text-sm font-medium text-stone-500">{item.description}</p>
        </Link>
        <div className="mt-4 flex items-center justify-between gap-3">
          <div>
            <p className="text-lg font-black">${price.toFixed(2)}</p>
            {originalPrice ? <p className="text-xs font-bold text-stone-400 line-through">${originalPrice.toFixed(2)}</p> : null}
          </div>
          <Button className="h-12 w-12 px-0" disabled={!item.is_available} onClick={() => onAdd(item)} title="Add to cart">
            <Plus size={20} />
          </Button>
        </div>
      </div>
    </Card>
  )
}
