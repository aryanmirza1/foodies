import { Minus, Plus } from 'lucide-react'
import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { LoadingScreen } from '../../components/ui/LoadingScreen'
import { useMenuItem } from '../../lib/api'
import { addToCart } from '../../stores/cartStore'

export function FoodDetailsPage() {
  const { slug } = useParams()
  const { data: item, error, loading } = useMenuItem(slug)
  const [quantity, setQuantity] = useState(1)
  const [note, setNote] = useState('')

  if (loading) {
    return <LoadingScreen label="Loading item" />
  }

  if (error || !item) {
    return (
      <Card className="p-6 text-center">
        <p className="text-lg font-black">Item unavailable</p>
        <Link className="mt-4 inline-block text-sm font-black text-amber-500" to="/menu">
          Back to menu
        </Link>
      </Card>
    )
  }

  const price = Number(item.discount_price ?? item.price)

  return (
    <div className="space-y-5">
      <div className="overflow-hidden rounded-[2rem] bg-gradient-to-br from-amber-100 via-white to-stone-100 shadow-sm">
        {item.image_path ? (
          <img alt={item.name} className="h-72 w-full object-cover" src={item.image_path} />
        ) : (
          <div className="grid h-72 place-items-center text-7xl font-black text-amber-400">{item.name.charAt(0)}</div>
        )}
      </div>

      <section>
        <p className="text-sm font-black uppercase tracking-[0.2em] text-amber-500">{item.category?.name}</p>
        <h2 className="mt-2 text-[2.3rem] font-black leading-10">{item.name}</h2>
        <p className="mt-3 text-base font-semibold leading-7 text-stone-500">{item.description}</p>
      </section>

      <Card className="p-4">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-bold text-stone-500">Price</p>
            <p className="text-2xl font-black">${price.toFixed(2)}</p>
          </div>
          <div className="flex items-center gap-3 rounded-full bg-stone-100 p-1">
            <button className="quantity-button" onClick={() => setQuantity(Math.max(1, quantity - 1))} type="button">
              <Minus size={18} />
            </button>
            <span className="w-7 text-center text-lg font-black">{quantity}</span>
            <button className="quantity-button" onClick={() => setQuantity(quantity + 1)} type="button">
              <Plus size={18} />
            </button>
          </div>
        </div>
      </Card>

      <label className="block">
        <span className="mb-2 block text-sm font-black text-stone-500">Special note</span>
        <textarea
          className="min-h-28 w-full resize-none rounded-[1.4rem] border border-stone-200 bg-white p-4 text-base font-semibold outline-none focus:border-amber-400"
          onChange={(event) => setNote(event.target.value)}
          value={note}
        />
      </label>

      <Button className="w-full" disabled={!item.is_available} onClick={() => addToCart(item, quantity, note)}>
        Add to Cart
      </Button>
    </div>
  )
}
