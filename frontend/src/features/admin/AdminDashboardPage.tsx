import { ClipboardList, DollarSign, Menu, Star } from 'lucide-react'
import { Card } from '../../components/ui/Card'

const stats = [
  { label: 'New Orders', value: '-', icon: ClipboardList },
  { label: 'Revenue', value: '-', icon: DollarSign },
  { label: 'Menu Items', value: '-', icon: Menu },
  { label: 'Rating', value: '-', icon: Star },
]

export function AdminDashboardPage() {
  return (
    <div className="space-y-5">
      <section>
        <h2 className="text-[2rem] font-black leading-9">Dashboard</h2>
        <p className="mt-1 text-sm font-semibold text-stone-500">Mobile control center</p>
      </section>
      <div className="grid grid-cols-2 gap-3">
        {stats.map((stat) => (
          <Card className="p-4" key={stat.label}>
            <div className="grid h-12 w-12 place-items-center rounded-2xl bg-stone-950 text-amber-400">
              <stat.icon size={22} />
            </div>
            <p className="mt-5 text-3xl font-black">{stat.value}</p>
            <p className="text-xs font-black uppercase tracking-[0.16em] text-stone-500">{stat.label}</p>
          </Card>
        ))}
      </div>
    </div>
  )
}
