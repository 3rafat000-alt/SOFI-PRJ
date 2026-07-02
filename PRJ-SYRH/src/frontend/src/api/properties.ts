import client from './client';
import type { PropertyCard, PropertyDetail, PropertyType } from './client';
export type { PropertyCard };

export interface AgencyDetailData {
  id: number;
  name: string;
  slug: string;
  logo_path: string | null;
  cover_path: string | null;
  address: string | null;
  description_ar: string | null;
  description_en: string | null;
  properties_count: number;
  agents_count: number;
  properties: PropertyCard[];
}

export interface Stats {
  total_properties: number;
  total_agents: number;
  total_agencies: number;
  total_governorates: number;
  happy_clients: number;
  satisfaction_pct: number;
}

export interface Testimonial {
  id: number;
  name: string;
  role: string;
  role_ar: string;
  role_en: string;
  avatar_path: string | null;
  rating: number;
  quote: string;
  quote_ar: string;
  quote_en: string;
}

export interface AgencyPublic {
  id: number;
  name: string;
  slug: string;
  logo_url: string | null;
  /** @deprecated use logo_url */
  logo_path: string | null;
  description_ar: string | null;
  description_en: string | null;
  properties_count: number;
  agents_count: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: { total: number; per_page: number; current_page: number; last_page: number };
}

export async function fetchFeatured(): Promise<PropertyCard[]> {
  const { data } = await client.get('/properties/featured');
  return data.data ?? [];
}

export async function fetchHotDeals(): Promise<PropertyCard[]> {
  const { data } = await client.get('/properties/hot-deals');
  return data.data ?? [];
}

export async function fetchStats(): Promise<Stats> {
  const { data } = await client.get('/stats');
  return data.data;
}

export async function fetchTestimonials(): Promise<Testimonial[]> {
  const { data } = await client.get('/testimonials');
  return data.data ?? [];
}

export async function fetchPropertyTypes(): Promise<PropertyType[]> {
  const { data } = await client.get('/property-types');
  return data.data ?? [];
}

export async function fetchAgencies(params?: Record<string, string>): Promise<AgencyPublic[]> {
  const { data } = await client.get('/agencies', { params });
  return data.data ?? [];
}

export async function fetchAgencyDetail(slug: string): Promise<AgencyDetailData> {
  const { data } = await client.get(`/agencies/${slug}`);
  return data.data;
}

export interface SettingsPublic {
  site_name_ar: string;
  site_name_en: string;
  site_description_ar: string;
  site_description_en: string;
  contact_email: string;
  contact_phone: string;
  whatsapp_number: string;
  facebook_url: string | null;
  instagram_url: string | null;
  twitter_url: string | null;
  telegram_url: string | null;
  address_ar: string;
  address_en: string;
}

export async function fetchSettings(): Promise<SettingsPublic> {
  const { data } = await client.get('/settings/public');
  return data.data;
}

export async function fetchProperties(params?: Record<string, string>): Promise<PaginatedResponse<PropertyCard>> {
  const { data } = await client.get('/properties', { params });
  return data;
}

export async function fetchProperty(slug: string): Promise<PropertyDetail> {
  const { data } = await client.get(`/properties/${slug}`);
  return data.data;
}

export async function submitInquiry(propertyId: number, payload: { name: string; phone: string; email?: string; message?: string }): Promise<void> {
  await client.post(`/properties/${propertyId}/inquiries`, payload);
}

export interface AgencyProperty {
  id: number;
  slug: string;
  title_ar: string;
  title_en: string;
  price: number;
  currency: string;
  purpose: string;
  status: string;
  cover: string | null;
}

export async function fetchAgencyProperties(agencyId: number): Promise<AgencyProperty[]> {
  const { data } = await client.get(`/agencies/${agencyId}/properties`);
  return data.data;
}
