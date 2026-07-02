import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { MapPin, Bed, Bath, Maximize2, Home } from 'lucide-react';
import { Link } from 'react-router-dom';
import type { PropertyCard } from '../api/client';

interface Props {
  property: PropertyCard;
}

export default function PropertyCard({ property }: Props) {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;

  const title = isAr ? property.title_ar : property.title_en;
  const price = Number(property.price).toLocaleString();
  const isRent = property.purpose === 'rent';
  const [imgError, setImgError] = useState(false);

  return (
    <div className="group/card bg-white rounded-2xl overflow-hidden border border-beige-dark/20 shadow-sm hover:border-primary/20 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1">
      <Link to={`/properties/${property.slug}`} className="block">
        {/* Image */}
        <div className="relative aspect-[4/3] overflow-hidden bg-beige">
          {property.cover_image?.path && !imgError ? (
            <img src={property.cover_image.path} alt={title}
              className="w-full h-full object-cover group-hover/card:scale-105 transition-transform duration-500"
              loading="lazy"
              onError={() => setImgError(true)} />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-stone-300">
              <Home className="w-12 h-12" />
            </div>
          )}

          {/* Featured badge */}
          {property.is_featured && (
            <div className="absolute top-3 ltr:left-3 rtl:right-3">
              <span className="bg-gold text-white text-[10px] font-bold px-2 py-1 rounded-md shadow-md">
                {L('مميز', 'Featured')}
              </span>
            </div>
          )}

          {/* Purpose badge */}
          <div className="absolute top-3 ltr:right-3 rtl:left-3">
            <span className={`text-white text-[10px] font-bold px-2 py-1 rounded-md shadow-md ${
              isRent ? 'bg-blue-500' : 'bg-emerald-500'
            }`}>
              {isRent ? t('property.purpose.rent') : t('property.purpose.sale')}
            </span>
          </div>

          {/* Price overlay */}
          <div className="absolute bottom-3 ltr:left-3 rtl:right-3">
            <span className="inline-flex items-center bg-white/90 backdrop-blur-sm text-primary font-bold text-sm px-2.5 py-1 rounded-lg shadow-sm gap-0.5">
              ${price}
              {isRent && <span className="text-stone-400 text-[10px] font-normal">/{t('property.perMonth')}</span>}
            </span>
          </div>
        </div>
      </Link>

      {/* Content */}
      <div className="p-3.5 md:p-4">
        <Link to={`/properties/${property.slug}`} className="block">
          <h3 className="text-stone-800 font-bold text-sm leading-snug line-clamp-1 group-hover/card:text-primary transition-colors">
            {title}
          </h3>

          <div className="flex items-center gap-1 text-stone-400 text-[11px] mt-1 mb-2.5">
            <MapPin className="w-3 h-3 shrink-0" />
            <span className="truncate">{property.governorate?.name ?? ''}</span>
          </div>
        </Link>

        {/* Stats row */}
        <div className="flex items-center gap-3 pt-2.5 border-t border-beige-dark/20 text-stone-500 text-[11px] mb-2.5">
          <span className="flex items-center gap-1">
            <Maximize2 className="w-3 h-3" /> {property.area_sqm}
          </span>
          <span className="flex items-center gap-1">
            <Bed className="w-3 h-3" /> {property.bedrooms}
          </span>
          <span className="flex items-center gap-1">
            <Bath className="w-3 h-3" /> {property.bathrooms}
          </span>
        </div>
      </div>
    </div>
  );
}
