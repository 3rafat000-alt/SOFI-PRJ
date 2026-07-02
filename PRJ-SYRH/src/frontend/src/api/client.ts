import axios from 'axios';
import i18n from '../i18n';

const client = axios.create({
  baseURL: '/api/v1',
  headers: { 'Accept': 'application/json' },
});

client.interceptors.request.use((config) => {
  const lang = i18n.language;
  config.headers['Accept-Language'] = lang;
  const token = localStorage.getItem('auth_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export default client;

export interface Governorate {
  id: number;
  slug: string;
  name: string;
  name_ar: string;
  name_en: string;
  lat: string;
  lng: string;
  properties_count: number;
}

export interface Area {
  id: number;
  governorate_id: number;
  slug: string;
  name: string;
  name_ar: string;
  name_en: string;
  lat: string;
  lng: string;
  properties_count: number;
}

export interface PropertyType {
  id: number;
  slug: string;
  name: string;
  name_ar: string;
  name_en: string;
  icon: string;
  sort: number;
  listings_count: number;
}

export interface Amenity {
  id: number;
  slug: string;
  name: string;
  name_ar: string;
  name_en: string;
  icon: string;
}

export interface Agent {
  id: number;
  name: string;
  photo_url: string | null;
  phone: string;
  email: string;
  agency: {
    id: number;
    name: string;
    slug: string;
    logo_url: string | null;
  };
  properties_count: number;
}

export interface CoverImage {
  path: string;
  alt_ar: string | null;
  alt_en: string | null;
}

export interface PropertyCard {
  id: number;
  ref_code: string;
  slug: string;
  title: string;
  title_ar: string;
  title_en: string;
  purpose: 'sale' | 'rent';
  status: string;
  price: string;
  currency: string;
  rent_period: string | null;
  area_sqm: number;
  bedrooms: number;
  bathrooms: number;
  is_featured: boolean;
  is_hot_deal: boolean;
  cover_image: CoverImage;
  governorate: { id: number; name: string; slug: string };
  area: { id: number; name: string; slug: string };
  agency?: { id: number; name: string; slug: string; logo_path: string | null };
}

export interface PropertyImage {
  id: number;
  path: string;
  alt_ar: string | null;
  alt_en: string | null;
  sort: number;
}

export interface PropertyDetail extends PropertyCard {
  description: string;
  description_ar: string;
  description_en: string;
  latitude: string;
  longitude: string;
  agent: Agent | null;
  images: PropertyImage[];
  amenities: Amenity[];
  created_at: string;
  views_count: number;
  year_built: number | null;
  floor: number | null;
  parking: number | null;
  furnished: boolean | null;
}
