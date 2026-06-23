import { useMemo, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { CategoryChip } from '../../components/ui/CategoryChip'
import { FoodCard } from '../../components/ui/FoodCard'
import { LoadingScreen } from '../../components/ui/LoadingScreen'
import { SearchBar } from '../../components/ui/SearchBar'
import { useCategories, useHomeSection, useMenuItems } from '../../lib/api'
import { addToCart } from '../../stores/cartStore'

export function MenuPage() {
  const [searchParams] = useSearchParams()
  const [query, setQuery] = useState('')
  const [selectedCategory, setSelectedCategory] = useState(searchParams.get('category') ?? 'all')
  const categories = useCategories()
  const menuItems = useMenuItems()
  const home = useHomeSection()

  const filteredItems = useMemo(() => {
    return (menuItems.data ?? []).filter((item) => {
      const matchesCategory = selectedCategory === 'all' || item.category?.slug === selectedCategory
      const haystack = `${item.name} ${item.description ?? ''} ${item.category?.name ?? ''}`.toLowerCase()
      return matchesCategory && haystack.includes(query.toLowerCase())
    })
  }, [menuItems.data, query, selectedCategory])

  if (categories.loading || menuItems.loading) {
    return <LoadingScreen label="Loading menu" />
  }

  return (
    <div className="space-y-5">
      <section>
        <h2 className="text-[2rem] font-black leading-9">Menu</h2>
        <p className="mt-1 text-sm font-semibold text-stone-500">{filteredItems.length} items available</p>
      </section>

      <SearchBar onChange={setQuery} placeholder={home.data?.search_placeholder} value={query} />

      <div className="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1">
        <CategoryChip active={selectedCategory === 'all'} label="All" onClick={() => setSelectedCategory('all')} />
        {(categories.data ?? []).map((category) => (
          <CategoryChip
            active={selectedCategory === category.slug}
            key={category.id}
            label={category.name}
            onClick={() => setSelectedCategory(category.slug)}
          />
        ))}
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        {filteredItems.map((item) => (
          <FoodCard item={item} key={item.id} onAdd={(selected) => addToCart(selected)} />
        ))}
      </div>

      {!filteredItems.length ? (
        <div className="rounded-[1.5rem] border border-dashed border-stone-300 p-8 text-center">
          <p className="text-lg font-black">No items found</p>
        </div>
      ) : null}
    </div>
  )
}
