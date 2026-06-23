import { ArrowRight, ShoppingBag } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Card } from '../../components/ui/Card'
import { FoodCard } from '../../components/ui/FoodCard'
import { LoadingScreen } from '../../components/ui/LoadingScreen'
import { SearchBar } from '../../components/ui/SearchBar'
import { useBanners, useCategories, useHomeSection, useMenuItems, useSettings } from '../../lib/api'
import { addToCart, useCart } from '../../stores/cartStore'

export function HomePage() {
  const [query, setQuery] = useState('')
  const settings = useSettings()
  const home = useHomeSection()
  const banners = useBanners()
  const categories = useCategories()
  const menuItems = useMenuItems()
  const cart = useCart()
  const popularItems = (menuItems.data ?? []).filter((item) => item.is_popular).slice(0, 4)
  const activeBanner = banners.data?.[0]

  if (settings.loading || home.loading || categories.loading || menuItems.loading) {
    return <LoadingScreen label="Preparing menu" />
  }

  return (
    <div className="space-y-6">
      <section>
        <p className="text-lg font-black text-stone-500">Hello, there!</p>
        <h2 className="mt-1 text-[2.15rem] font-black leading-9">{home.data?.hero_title}</h2>
        <p className="mt-2 text-base font-semibold text-stone-500">{home.data?.hero_subtitle}</p>
      </section>

      <SearchBar onChange={setQuery} placeholder={home.data?.search_placeholder} value={query} />

      <Card className="overflow-hidden border-0 bg-stone-950 text-white">
        <div className="relative min-h-52 p-6">
          <div className="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(251,191,36,0.75),transparent_38%),linear-gradient(135deg,#111111,#3f3416)]" />
          {activeBanner?.image_path ? <img alt="" className="absolute inset-0 h-full w-full object-cover opacity-45" src={activeBanner.image_path} /> : null}
          <div className="relative flex min-h-40 flex-col justify-end">
            <p className="text-sm font-black uppercase tracking-[0.2em] text-amber-300">{settings.data?.restaurant_name}</p>
            <h3 className="mt-3 max-w-72 text-3xl font-black leading-9">{activeBanner?.title}</h3>
            {activeBanner?.subtitle ? <p className="mt-2 max-w-72 text-sm font-semibold text-white/75">{activeBanner.subtitle}</p> : null}
          </div>
        </div>
      </Card>

      {cart.count > 0 ? (
        <Link to="/cart">
          <Card className="flex items-center justify-between p-4">
            <div className="flex items-center gap-3">
              <div className="grid h-11 w-11 place-items-center rounded-full bg-amber-100 text-amber-500">
                <ShoppingBag size={21} />
              </div>
              <div>
                <p className="font-black">
                  {cart.count} item{cart.count === 1 ? '' : 's'}
                </p>
                <p className="text-sm font-semibold text-stone-500">${cart.total.toFixed(2)}</p>
              </div>
            </div>
            <ArrowRight size={22} />
          </Card>
        </Link>
      ) : null}

      <section>
        <div className="mb-3 flex items-center justify-between">
          <h2 className="text-xl font-black">{home.data?.category_section_title}</h2>
          <Link className="text-sm font-black text-amber-500" to="/menu">
            View All
          </Link>
        </div>
        <div className="grid grid-cols-3 gap-3">
          {(categories.data ?? []).slice(0, 6).map((category) => (
            <Link key={category.id} to={`/menu?category=${category.slug}`}>
              <Card className="grid aspect-square place-items-center p-3 text-center">
                <div>
                  <div className="mx-auto grid h-12 w-12 place-items-center rounded-full bg-amber-50 text-lg font-black text-amber-500">
                    {category.icon ?? category.name.charAt(0)}
                  </div>
                  <p className="mt-3 line-clamp-1 text-sm font-black">{category.name}</p>
                </div>
              </Card>
            </Link>
          ))}
        </div>
      </section>

      <section>
        <div className="mb-3 flex items-center justify-between">
          <h2 className="text-xl font-black">{home.data?.popular_section_title}</h2>
          <Link className="text-sm font-black text-amber-500" to="/menu">
            View All
          </Link>
        </div>
        <div className="grid gap-4 sm:grid-cols-2">
          {popularItems.map((item) => (
            <FoodCard item={item} key={item.id} onAdd={(selected) => addToCart(selected)} />
          ))}
        </div>
      </section>

      {settings.error || home.error || categories.error || menuItems.error ? (
        <Card className="p-4 text-sm font-bold text-red-500">API connection failed. Start the Laravel backend and refresh.</Card>
      ) : null}
    </div>
  )
}
