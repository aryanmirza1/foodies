import { Minus, Plus, Trash2 } from 'lucide-react'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { removeFromCart, setCartQuantity, useCart } from '../../stores/cartStore'

export function CartPage() {
  const cart = useCart()

  return (
    <div className="space-y-5">
      <section>
        <h2 className="text-[2rem] font-black leading-9">Cart</h2>
        <p className="mt-1 text-sm font-semibold text-stone-500">
          {cart.count} selected item{cart.count === 1 ? '' : 's'}
        </p>
      </section>

      {cart.items.length ? (
        <div className="space-y-3">
          {cart.items.map((item) => (
            <Card className="p-4" key={item.id}>
              <div className="flex gap-4">
                <div className="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-2xl bg-amber-50 text-2xl font-black text-amber-400">
                  {item.image_path ? <img alt={item.name} className="h-full w-full object-cover" src={item.image_path} /> : item.name.charAt(0)}
                </div>
                <div className="min-w-0 flex-1">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <h3 className="line-clamp-1 font-black">{item.name}</h3>
                      <p className="text-sm font-bold text-stone-500">${item.price.toFixed(2)}</p>
                    </div>
                    <button aria-label={`Remove ${item.name}`} className="text-red-400" onClick={() => removeFromCart(item.id)} type="button">
                      <Trash2 size={19} />
                    </button>
                  </div>
                  <div className="mt-3 flex items-center justify-between">
                    <div className="flex items-center gap-2 rounded-full bg-stone-100 p-1">
                      <button className="mini-quantity-button" onClick={() => setCartQuantity(item.id, item.quantity - 1)} type="button">
                        <Minus size={15} />
                      </button>
                      <span className="w-6 text-center text-sm font-black">{item.quantity}</span>
                      <button className="mini-quantity-button" onClick={() => setCartQuantity(item.id, item.quantity + 1)} type="button">
                        <Plus size={15} />
                      </button>
                    </div>
                    <p className="font-black">${(item.price * item.quantity).toFixed(2)}</p>
                  </div>
                </div>
              </div>
            </Card>
          ))}
        </div>
      ) : (
        <Card className="p-8 text-center">
          <p className="text-lg font-black">Cart is empty</p>
        </Card>
      )}

      <Card className="space-y-3 p-5">
        <div className="flex justify-between text-sm font-bold text-stone-500">
          <span>Subtotal</span>
          <span>${cart.subtotal.toFixed(2)}</span>
        </div>
        <div className="flex justify-between text-sm font-bold text-stone-500">
          <span>Delivery</span>
          <span>${cart.deliveryFee.toFixed(2)}</span>
        </div>
        <div className="h-px bg-stone-200" />
        <div className="flex justify-between text-xl font-black">
          <span>Total</span>
          <span>${cart.total.toFixed(2)}</span>
        </div>
      </Card>

      <Button className="w-full" disabled>
        Checkout
      </Button>
    </div>
  )
}
