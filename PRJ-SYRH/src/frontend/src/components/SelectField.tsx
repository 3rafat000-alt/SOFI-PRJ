import { useState, useRef, useEffect } from 'react';
import { ChevronDown, Check } from 'lucide-react';

interface Option {
  value: string;
  label: string;
}

interface Props {
  value: string;
  onChange: (value: string) => void;
  options: Option[];
  placeholder?: string;
  className?: string;
  selectClassName?: string;
  size?: 'sm' | 'md';
  /** 'default' | 'glass' — glass variant for dark/hero backgrounds */
  variant?: 'default' | 'glass';
  disabled?: boolean;
}

export default function SelectField({
  value, onChange, options, placeholder,
  className = '', selectClassName = '', size = 'sm',
  variant = 'default', disabled = false,
}: Props) {
  const [open, setOpen] = useState(false);
  const [openUp, setOpenUp] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Close on outside click
  useEffect(() => {
    if (!open) return;
    const handler = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  // Close on Escape
  useEffect(() => {
    if (!open) return;
    const handler = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setOpen(false);
    };
    document.addEventListener('keydown', handler);
    return () => document.removeEventListener('keydown', handler);
  }, [open]);

  // Decide open direction on mount
  useEffect(() => {
    if (!open || !containerRef.current) return;
    const rect = containerRef.current.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    setOpenUp(spaceBelow < 240);
  }, [open]);

  const selected = options.find(o => o.value === value);
  const displayText = selected?.label || placeholder || '';
  const isSm = size === 'sm';
  const baseBtn = isSm ? 'px-3 py-2 text-sm' : 'px-4 py-2.5 text-base';

  const isGlass = variant === 'glass';
  const btnDefaultStyle = isGlass
    ? 'bg-white/10 border-white/20 text-white backdrop-blur-sm'
    : 'border-beige-dark bg-white text-stone-700';

  return (
    <div className={`relative ${className}`} ref={containerRef}>
      {/* Trigger button */}
      <button
        type="button"
        onClick={() => { if (!disabled) setOpen(!open); }}
        className={`
          w-full flex items-center justify-between gap-2 rounded-xl border
          font-medium cursor-pointer text-right rtl:text-right
          transition-all duration-200
          focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary
          hover:border-primary/30
          ${baseBtn}
          ${disabled ? 'opacity-50 cursor-not-allowed' : ''}
          ${selected ? (isGlass ? 'text-white' : 'text-stone-800') : (isGlass ? 'text-white/60' : 'text-stone-400')}
          ${selectClassName || btnDefaultStyle}
          ${open ? 'border-primary ring-2 ring-primary/20' : ''}
        `}
      >
        <span className="truncate flex-1">{displayText}</span>
        <ChevronDown className={`w-4 h-4 shrink-0 transition-transform duration-200 ${open ? 'rotate-180' : ''} ${isGlass ? 'text-white/50' : 'text-stone-400'}`} />
      </button>

      {/* Dropdown */}
      {open && (
        <div
          ref={dropdownRef}
          className={`
            absolute z-50 w-full min-w-[160px] rounded-xl border shadow-xl overflow-hidden
            ${openUp ? 'bottom-full mb-1.5' : 'mt-1.5'}
            ${isGlass
              ? 'bg-stone-800/95 backdrop-blur-xl border-white/10'
              : 'bg-white border-beige-dark'
            }
          `}
          style={{
            animation: `${openUp ? 'dropdownUpIn' : 'dropdownIn'} 0.15s ease-out`,
          }}
        >
          <style>{`
            @keyframes dropdownIn{from{opacity:0;transform:translateY(-4px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}
            @keyframes dropdownUpIn{from{opacity:0;transform:translateY(4px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}
          `}</style>
          <div className="max-h-56 overflow-y-auto py-1">
            {placeholder && (
              <button
                type="button"
                onClick={() => { onChange(''); setOpen(false); }}
                className={`
                  w-full text-right rtl:text-right px-4 py-2.5 text-sm font-medium
                  transition-colors duration-100
                  ${!value
                    ? isGlass ? 'bg-primary/20 text-gold-light' : 'bg-primary/10 text-primary'
                    : isGlass ? 'text-stone-400 hover:bg-white/10 hover:text-white' : 'text-stone-500 hover:bg-beige hover:text-stone-700'
                  }
                `}
              >
                {placeholder}
              </button>
            )}
            {options.map((o) => {
              const isSelected = o.value === value;
              return (
                <button
                  key={o.value}
                  type="button"
                  onClick={() => { onChange(o.value); setOpen(false); }}
                  className={`
                    w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-right rtl:text-right
                    transition-colors duration-100
                    ${isSelected
                      ? isGlass ? 'bg-primary/20 text-gold-light font-semibold' : 'bg-primary/10 text-primary font-semibold'
                      : isGlass ? 'text-stone-300 hover:bg-white/10 hover:text-white' : 'text-stone-700 hover:bg-beige hover:text-stone-900'
                    }
                  `}
                >
                  {isSelected && (
                    <Check className={`w-4 h-4 shrink-0 ${isGlass ? 'text-gold-light' : 'text-primary'}`} />
                  )}
                  <span className="flex-1">{o.label}</span>
                </button>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
