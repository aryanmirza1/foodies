import { LockKeyhole } from 'lucide-react'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'

export function AdminLoginPage() {
  return (
    <div className="min-h-screen bg-[var(--color-app-bg)] px-5 py-10 text-stone-950">
      <div className="mx-auto flex min-h-[calc(100vh-5rem)] max-w-[480px] flex-col justify-center">
        <div className="mb-8">
          <div className="grid h-14 w-14 place-items-center rounded-2xl bg-stone-950 text-2xl font-black text-amber-400">F</div>
          <h1 className="mt-5 text-[2.3rem] font-black leading-10">Admin Login</h1>
        </div>
        <Card className="space-y-4 p-5">
          <label className="block">
            <span className="mb-2 block text-sm font-black text-stone-500">Email</span>
            <input className="form-field" type="email" />
          </label>
          <label className="block">
            <span className="mb-2 block text-sm font-black text-stone-500">Password</span>
            <input className="form-field" type="password" />
          </label>
          <Button className="w-full" disabled>
            <LockKeyhole size={19} />
            Sign In
          </Button>
        </Card>
      </div>
    </div>
  )
}
