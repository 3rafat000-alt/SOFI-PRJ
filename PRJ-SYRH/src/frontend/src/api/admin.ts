import client from './client';

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  status: string;
  locale: string;
  created_at: string;
  roles: { id: number; name: string }[];
}

export interface AdminAgency {
  id: number;
  name: string;
  slug: string;
  phone: string | null;
  email: string | null;
  status: string;
  verified_at: string | null;
  created_at: string;
  owner: { id: number; name: string; email: string } | null;
  subscription: { id: number; plan: { name_ar: string; name_en: string } } | null;
}

export interface AdminProperty {
  id: number;
  ref_code: string;
  slug: string;
  title_ar: string;
  title_en: string;
  status: string;
  created_at: string;
  agency: { id: number; name: string };
  type: { name_ar: string; name_en: string };
  governorate: { name_ar: string; name_en: string };
}

export interface SubscriptionPlan {
  id: number;
  name_ar: string;
  name_en: string;
  slug: string;
  price: string;
  currency: string;
  duration_days: number;
  max_properties: number;
  max_agents: number;
  is_featured: boolean;
  features: string[] | null;
  sort: number;
  is_active: boolean;
}

export interface ContactMessage {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  subject: string;
  message: string;
  is_read: boolean;
  created_at: string;
}

export interface ReviewItem {
  id: number;
  rating: number;
  comment: string;
  is_approved: boolean;
  created_at: string;
  user: { id: number; name: string };
  property: { id: number; title_ar: string; title_en: string };
}

export interface AdminDashboard {
  total_users: number;
  total_agencies: number;
  total_properties: number;
  total_inquiries: number;
  active_plans: number;
  pending_agencies: number;
  unread_messages: number;
  monthly_revenue: number;
  recent_users: { id: number; name: string; email: string; created_at: string }[];
  recent_agencies: { id: number; name: string; status: string; created_at: string }[];
  sakk: {
    configured: boolean;
    merchant_id: string | null;
    sandbox: boolean;
    agencies_linked: number;
    total_payments: number;
    total_revenue: number;
  };
}

interface PaginatedMeta {
  total: number; per_page: number; current_page: number; last_page: number;
}

export async function fetchAdminDashboard(): Promise<AdminDashboard> {
  const res = await client.get('/admin/dashboard');
  return res.data.data;
}

export async function fetchAdminUsers(page = 1): Promise<{ data: AdminUser[]; meta: PaginatedMeta }> {
  const res = await client.get(`/admin/users?page=${page}`);
  return res.data;
}

export async function updateAdminUser(id: number, data: Partial<AdminUser>): Promise<AdminUser> {
  const res = await client.put(`/admin/users/${id}`, data);
  return res.data.data;
}

export async function fetchAdminAgencies(page = 1): Promise<{ data: AdminAgency[]; meta: PaginatedMeta }> {
  const res = await client.get(`/admin/agencies?page=${page}`);
  return res.data;
}

export async function updateAdminAgency(id: number, data: Partial<AdminAgency>): Promise<AdminAgency> {
  const res = await client.put(`/admin/agencies/${id}`, data);
  return res.data.data;
}

export async function fetchAdminProperties(page = 1): Promise<{ data: AdminProperty[]; meta: PaginatedMeta }> {
  const res = await client.get(`/admin/properties?page=${page}`);
  return res.data;
}

export async function moderateProperty(id: number, data: { status: string; is_featured?: boolean; is_hot_deal?: boolean }): Promise<any> {
  const res = await client.put(`/admin/properties/${id}/moderate`, data);
  return res.data.data;
}

export async function fetchPlans(): Promise<SubscriptionPlan[]> {
  const res = await client.get('/admin/plans');
  return res.data.data;
}

export async function createPlan(data: Partial<SubscriptionPlan>): Promise<SubscriptionPlan> {
  const res = await client.post('/admin/plans', data);
  return res.data.data;
}

export async function updatePlan(id: number, data: Partial<SubscriptionPlan>): Promise<SubscriptionPlan> {
  const res = await client.put(`/admin/plans/${id}`, data);
  return res.data.data;
}

export async function fetchMessages(page = 1): Promise<{ data: ContactMessage[]; meta: PaginatedMeta }> {
  const res = await client.get(`/admin/messages?page=${page}`);
  return res.data;
}

export async function markMessageRead(id: number): Promise<ContactMessage> {
  const res = await client.post(`/admin/messages/${id}/read`);
  return res.data.data;
}

export async function fetchReviews(page = 1): Promise<{ data: ReviewItem[]; meta: PaginatedMeta }> {
  const res = await client.get(`/admin/reviews?page=${page}`);
  return res.data;
}

export async function approveReview(id: number): Promise<ReviewItem> {
  const res = await client.post(`/admin/reviews/${id}/approve`);
  return res.data.data;
}

export async function fetchSettings(): Promise<Record<string, { key: string; value: string }[]>> {
  const res = await client.get('/admin/settings');
  return res.data.data;
}

export async function updateSettings(settings: { key: string; value: string }[]): Promise<void> {
  await client.post('/admin/settings', { settings });
}

// -- Areas --

export interface AdminArea {
  id: number;
  name_ar: string;
  name_en: string;
  slug: string;
  lat: string | null;
  lng: string | null;
  governorate_id: number;
  governorate?: { id: number; name_ar: string; name_en: string };
  created_at: string;
}

export interface AdminGovernorate {
  id: number;
  name_ar: string;
  name_en: string;
  slug: string;
}

export async function fetchAdminAreas(page = 1): Promise<{ data: AdminArea[]; meta: PaginatedMeta }> {
  const res = await client.get(`/admin/areas?page=${page}`);
  return res.data;
}

export async function createAdminArea(data: { governorate_id: number; name_ar: string; name_en: string; lat?: string | number; lng?: string | number }): Promise<AdminArea> {
  const res = await client.post('/admin/areas', data);
  return res.data.data;
}

export async function updateAdminArea(id: number, data: Record<string, any>): Promise<AdminArea> {
  const res = await client.put(`/admin/areas/${id}`, data);
  return res.data.data;
}

export async function deleteAdminArea(id: number): Promise<void> {
  await client.delete(`/admin/areas/${id}`);
}

export async function fetchAdminGovernorates(): Promise<AdminGovernorate[]> {
  const res = await client.get('/admin/governorates');
  return res.data.data;
}
