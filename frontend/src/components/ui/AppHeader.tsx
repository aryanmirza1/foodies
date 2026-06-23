import type { ReactNode } from 'react'

type AppHeaderProps = {
  title: string
  subtitle?: string | null
  logoPath?: string | null
  rightAction?: ReactNode
}

export function AppHeader({ title, subtitle, logoPath, rightAction }: AppHeaderProps) {
  return (
    <header className="sticky top-0 z-30 border-b border-stone-200/80 bg-white/95 px-5 pb-4 pt-6 backdrop-blur">
      <div className="flex items-center gap-3">
        <div className="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-xl bg-stone-950 text-lg font-black text-amber-400 shadow-sm">
          {logoPath ? <img alt="" className="h-full w-full object-cover" src={logoPath} /> : 'F'}
        </div>
        <div className="min-w-0 flex-1">
          <h1 className="truncate text-[1.6rem] font-black leading-7 tracking-normal">{title}</h1>
          {subtitle ? <p className="mt-0.5 truncate text-sm font-medium text-stone-500">{subtitle}</p> : null}
        </div>
        {rightAction}
      </div>
    </header>
  )
}
