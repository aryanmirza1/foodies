import type { LucideIcon } from 'lucide-react'
import { NavLink } from 'react-router-dom'

export type BottomNavItem = {
  to: string
  label: string
  icon: LucideIcon
  badge?: number
}

type BottomNavProps = {
  items: BottomNavItem[]
}

export function BottomNav({ items }: BottomNavProps) {
  return (
    <nav className="fixed inset-x-0 bottom-4 z-40 mx-auto w-[min(92vw,500px)] rounded-full bg-stone-950 px-3 py-2 shadow-2xl">
      <div className="grid grid-cols-5 items-center">
        {items.map((item) => (
          <NavLink
            className={({ isActive }) =>
              `relative mx-auto grid h-14 w-14 place-items-center rounded-full transition ${
                isActive
                  ? '-translate-y-5 bg-amber-400 text-stone-950 shadow-[0_10px_28px_rgba(251,191,36,0.45)] ring-8 ring-[var(--color-app-bg)]'
                  : 'text-stone-400'
              }`
            }
            end={item.to === '/' || item.to === '/admin'}
            key={item.to}
            title={item.label}
            to={item.to}
          >
            <item.icon size={24} strokeWidth={2.1} />
            {item.badge ? <span className="cart-badge">{item.badge}</span> : null}
          </NavLink>
        ))}
      </div>
    </nav>
  )
}
