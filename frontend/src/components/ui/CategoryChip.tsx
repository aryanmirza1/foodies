type CategoryChipProps = {
  label: string
  active?: boolean
  onClick: () => void
}

export function CategoryChip({ label, active = false, onClick }: CategoryChipProps) {
  return (
    <button
      className={`h-11 shrink-0 rounded-full border px-4 text-sm font-bold transition ${
        active ? 'border-stone-950 bg-stone-950 text-white' : 'border-stone-200 bg-white text-stone-600'
      }`}
      onClick={onClick}
      type="button"
    >
      {label}
    </button>
  )
}
