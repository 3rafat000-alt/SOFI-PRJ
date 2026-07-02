import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { Heart, Loader2, Trash2 } from 'lucide-react';
import { fetchFavorites, toggleFavorite, type FavoriteItem } from '../../api/auth';
import PropertyCard from '../../components/PropertyCard';

export default function UserFavorites() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [favorites, setFavorites] = useState<FavoriteItem[]>([]);
  const [loading, setLoading] = useState(true);

  const load = async () => {
    setLoading(true);
    try { setFavorites(await fetchFavorites()); } catch {}
    setLoading(false);
  };

  useEffect(() => { load(); }, []);

  const handleRemove = async (propertyId: number) => {
    await toggleFavorite(propertyId);
    load();
  };

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-6">{isAr ? 'المفضلة' : 'My Favorites'}</h1>

      {loading ? (
        <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
      ) : favorites.length === 0 ? (
        <div className="card-3d p-12 text-center">
          <Heart className="w-12 h-12 text-stone-300 mx-auto mb-3" />
          <p className="text-stone-500 mb-2">{isAr ? 'لا توجد عقارات في المفضلة' : 'No favorite properties yet'}</p>
          <Link to="/properties" className="btn-primary text-sm inline-flex items-center gap-2 !py-2 !px-4">
            {isAr ? 'تصفح العقارات' : 'Browse Properties'}
          </Link>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {favorites.map(fav => (
            <div key={fav.id} className="relative group">
              <PropertyCard property={fav.property} />
              <button onClick={() => handleRemove(fav.property.id)}
                className="absolute top-2 right-2 w-8 h-8 rounded-lg bg-white/80 backdrop-blur-sm flex items-center justify-center text-red-500 hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100">
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
