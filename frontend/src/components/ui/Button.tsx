import type { ButtonHTMLAttributes, PropsWithChildren } from 'react'

type ButtonProps = PropsWithChildren<ButtonHTMLAttributes<HTMLButtonElement>> & {
  variant?: 'primary' | 'secondary' | 'ghost'
}

export function Button({ children, className = '', variant = 'primary', ...props }: ButtonProps) {
  const variants = {
    primary: 'bg-amber-400 text-stone-950 shadow-[0_12px_24px_rgba(251,191,36,0.28)]',
    secondary: 'border border-stone-200 bg-white text-stone-950',
    ghost: 'bg-transparent text-stone-950',
  }

  return (
    <button
      className={`inline-flex min-h-12 items-center justify-center gap-2 rounded-full px-5 text-base font-black transition active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 ${variants[variant]} ${className}`}
      {...props}
    >
      {children}
    </button>
  )
}
