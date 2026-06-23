import { Search } from 'lucide-react'

type SearchBarProps = {
  value: string
  onChange: (value: string) => void
  placeholder?: string
}

export function SearchBar({ value, onChange, placeholder = 'Search menu...' }: SearchBarProps) {
  return (
    <label className="flex h-14 items-center gap-3 rounded-[1.3rem] border border-stone-200 bg-white px-4 shadow-sm">
      <Search className="text-stone-400" size={20} />
      <input
        className="min-w-0 flex-1 bg-transparent text-base font-semibold outline-none placeholder:text-stone-400"
        onChange={(event) => onChange(event.target.value)}
        placeholder={placeholder}
        value={value}
      />
    </label>
  )
}
