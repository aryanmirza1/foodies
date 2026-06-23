import { UserRound } from 'lucide-react'
import { Card } from '../../components/ui/Card'

export function ProfilePagePlaceholder() {
  return (
    <div className="space-y-5">
      <h2 className="text-[2rem] font-black leading-9">Profile</h2>
      <Card className="grid min-h-80 place-items-center p-8 text-center">
        <div>
          <div className="mx-auto grid h-16 w-16 place-items-center rounded-full bg-amber-50 text-amber-400">
            <UserRound size={30} />
          </div>
          <p className="mt-5 text-xl font-black">Guest customer</p>
        </div>
      </Card>
    </div>
  )
}
