import type { HTMLAttributes, PropsWithChildren } from 'react'

export function Card({ children, className = '', ...props }: PropsWithChildren<HTMLAttributes<HTMLDivElement>>) {
  return (
    <div
      className={`rounded-[1.6rem] border border-stone-200/80 bg-white shadow-[0_12px_30px_rgba(28,25,23,0.08)] ${className}`}
      {...props}
    >
      {children}
    </div>
  )
}
