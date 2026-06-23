type LoadingScreenProps = {
  label?: string
}

export function LoadingScreen({ label = 'Loading' }: LoadingScreenProps) {
  return (
    <div className="grid min-h-[45vh] place-items-center">
      <div className="text-center">
        <div className="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-amber-200 border-t-amber-400" />
        <p className="mt-4 text-sm font-bold text-stone-500">{label}</p>
      </div>
    </div>
  )
}
