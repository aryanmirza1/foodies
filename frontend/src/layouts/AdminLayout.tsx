import { ClipboardList, Menu, Printer, Settings, UserRound } from 'lucide-react'
import { Outlet } from 'react-router-dom'
import { AppHeader } from '../components/ui/AppHeader'
import { BottomNav } from '../components/ui/BottomNav'

export function AdminLayout() {
  return (
    <div className="min-h-screen bg-[var(--color-app-bg)] text-stone-950">
      <div className="mx-auto flex min-h-screen w-full max-w-[540px] flex-col bg-[var(--color-app-bg)]">
        <AppHeader subtitle="Admin" title="Foodies" />
        <main className="flex-1 px-5 pb-32 pt-4">
          <Outlet />
        </main>
        <BottomNav
          items={[
            { to: '/admin', label: 'Dashboard', icon: Settings },
            { to: '/admin/orders', label: 'Orders', icon: ClipboardList },
            { to: '/admin/menu', label: 'Menu', icon: Menu },
            { to: '/admin/print', label: 'Print', icon: Printer },
            { to: '/admin/profile', label: 'Profile', icon: UserRound },
          ]}
        />
      </div>
    </div>
  )
}
