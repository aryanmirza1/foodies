import { Bell, ClipboardList, Home, Menu, ShoppingBag, UserRound } from 'lucide-react'
import { Outlet } from 'react-router-dom'
import { AppHeader } from '../components/ui/AppHeader'
import { BottomNav } from '../components/ui/BottomNav'
import { LoadingScreen } from '../components/ui/LoadingScreen'
import { useSettings } from '../lib/api'
import { useCart } from '../stores/cartStore'

export function CustomerLayout() {
  const { data: settings, loading } = useSettings()
  const cart = useCart()

  return (
    <div className="min-h-screen bg-[var(--color-app-bg)] text-stone-950">
      <div className="mx-auto flex min-h-screen w-full max-w-[540px] flex-col bg-[var(--color-app-bg)]">
        <AppHeader
          logoPath={settings?.logo_path}
          rightAction={
            <button className="icon-button relative" type="button" aria-label="Notifications">
              <Bell size={21} />
              {cart.count > 0 ? <span className="notification-dot">{cart.count}</span> : null}
            </button>
          }
          subtitle={settings?.tagline}
          title={settings?.app_name ?? 'Foodies'}
        />
        <main className="flex-1 px-5 pb-32 pt-4">
          {loading && !settings ? <LoadingScreen label="Loading Foodies" /> : <Outlet />}
        </main>
        <BottomNav
          items={[
            { to: '/', label: 'Home', icon: Home },
            { to: '/menu', label: 'Menu', icon: Menu },
            { to: '/cart', label: 'Cart', icon: ShoppingBag, badge: cart.count },
            { to: '/orders', label: 'Orders', icon: ClipboardList },
            { to: '/profile', label: 'Profile', icon: UserRound },
          ]}
        />
      </div>
    </div>
  )
}
