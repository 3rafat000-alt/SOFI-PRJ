import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { Search, Loader2, Trash2, Clock } from 'lucide-react';
import { fetchSavedSearches, deleteSavedSearch, type SavedSearchItem } from '../../api/auth';

export default function UserSearches() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [searches, setSearches] = useState<SavedSearchItem[]>([]);
  const [loading, setLoading] = useState(true);

  const load = async () => {
    setLoading(true);
    try { setSearches(await fetchSavedSearches()); } catch {}
    setLoading(false);
  };

  useEffect(() => { load(); }, []);

  const handleDelete = async (id: number) => {
    await deleteSavedSearch(id);
    load();
  };

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-6">{isAr ? 'عمليات البحث المحفوظة' : 'Saved Searches'}</h1>

      {loading ? (
        <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
      ) : searches.length === 0 ? (
        <div className="card-3d p-12 text-center">
          <Search className="w-12 h-12 text-stone-300 mx-auto mb-3" />
          <p className="text-stone-500">{isAr ? 'لا توجد عمليات بحث محفوظة' : 'No saved searches yet'}</p>
          <p className="text-xs text-stone-400 mt-1 mb-4">{isAr ? 'احفظ معايير البحث لتعود إليها لاحقاً' : 'Save your search criteria to come back later'}</p>
          <Link to="/properties" className="btn-primary text-sm inline-flex items-center gap-2 !py-2 !px-4">
            {isAr ? 'تصفح العقارات' : 'Browse Properties'}
          </Link>
        </div>
      ) : (
        <div className="space-y-3">
          {searches.map(s => {
            let filters: Record<string, string> = {};
            try { filters = JSON.parse(s.filters); } catch {}

            const filterLabels: Record<string, string> = {
              type: isAr ? 'النوع' : 'Type',
              purpose: isAr ? 'الغرض' : 'Purpose',
              governorate_id: isAr ? 'المحافظة' : 'Governorate',
              area_id: isAr ? 'المنطقة' : 'Area',
              min_price: isAr ? 'السعر من' : 'Min Price',
              max_price: isAr ? 'السعر إلى' : 'Max Price',
              min_area: isAr ? 'المساحة من' : 'Min Area',
              max_area: isAr ? 'المساحة إلى' : 'Max Area',
              bedrooms: isAr ? 'غرف النوم' : 'Bedrooms',
              bathrooms: isAr ? 'الحمامات' : 'Bathrooms',
            };

            return (
              <div key={s.id} className="card-3d p-5 flex items-center justify-between group hover:shadow-md transition-all">
                <div className="flex-1">
                  <div className="font-medium text-stone-900 flex items-center gap-2">
                    <Search className="w-4 h-4 text-primary" />
                    {s.name}
                  </div>
                  {Object.keys(filters).length > 0 && (
                    <div className="flex flex-wrap gap-1.5 mt-2">
                      {Object.entries(filters).map(([k, v]) => (
                        <span key={k} className="inline-block px-2 py-0.5 bg-beige rounded-lg text-xs text-stone-500">
                          {filterLabels[k] || k}: {v}
                        </span>
                      ))}
                    </div>
                  )}
                  <div className="flex items-center gap-1 mt-2 text-xs text-stone-400">
                    <Clock className="w-3 h-3" />
                    {new Date(s.created_at).toLocaleDateString(isAr ? 'ar' : 'en')}
                  </div>
                </div>
                <button onClick={() => handleDelete(s.id)}
                  className="p-2 rounded-lg text-stone-400 hover:text-red-500 hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100">
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
