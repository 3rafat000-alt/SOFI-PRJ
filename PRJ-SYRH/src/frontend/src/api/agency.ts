import client from './client';
import type { PropertyCard } from './client';

export interface AgencyStats {
  total_properties: number;
  active_listings: number;
  total_agents: number;
  total_inquiries: number;
  pending_inquiries: number;
  monthly_views: number;
  total_deals: number;
  confirmed_deals: number;
  total_commission: number;
  monthly_commission: number;
}

export interface AgencyAgent {
  id: number;
  display_name: string;
  email: string | null;
  phone: string;
  whatsapp: string | null;
  bio_ar: string | null;
  bio_en: string | null;
  properties_count?: number;
  created_at: string;
}

export interface AgencyInquiry {
  id: number;
  name: string;
  phone: string;
  email: string;
  message: string;
  status: 'new' | 'contacted' | 'closed';
  created_at: string;
  property: { id: number; slug: string; title_ar: string; title_en: string };
  agent: { id: number; display_name: string } | null;
}

export interface AgencyDeal {
  id: number;
  type: 'sale' | 'rent';
  price: number;
  currency: string;
  status: 'pending' | 'confirmed' | 'cancelled';
  deal_date: string;
  client_name: string;
  client_phone: string | null;
  notes: string | null;
  commission_rate: number;
  commission_amount: number;
  property: { id: number; slug: string; title_ar: string; title_en: string };
  agent: { id: number; display_name: string } | null;
  created_at: string;
}

export interface AgencyUsage {
  properties: { current: number; max: number };
  agents: { current: number; max: number };
}

export interface AgencySubscription {
  current_subscription: {
    id: number;
    status: 'trial' | 'active' | 'cancelled' | 'expired';
    start_at: string;
    end_at: string;
    trial_ends_at: string | null;
    plan: {
      id: number;
      name_ar: string;
      name_en: string;
      price: number;
      currency: string;
      duration_days: number;
      max_properties: number;
      max_agents: number;
      features: string[];
    };
  } | null;
  available_plans: Array<{
    id: number;
    name_ar: string;
    name_en: string;
    description_ar: string;
    description_en: string;
    price: number;
    currency: string;
    duration_days: number;
    max_properties: number;
    max_agents: number;
    features: string[];
    is_active: boolean;
    sort: number;
  }>;
  usage?: AgencyUsage;
}

export interface AgencyProfile {
  id: number;
  name: string;
  phone: string;
  whatsapp: string | null;
  email: string;
  description_ar: string | null;
  description_en: string | null;
  address: string | null;
  logo_url: string | null;
  cover_url: string | null;
  commission_rate: number;
  governorate_id: number | null;
  area_id: number | null;
  lat: number | null;
  lng: number | null;
  governorate?: { id: number; name_ar: string; name_en: string } | null;
  area?: { id: number; name_ar: string; name_en: string } | null;
}

export interface SakkAccount {
  sakk_merchant_id: string | null;
  sakk_verified: boolean;
  sakk_verified_at: string | null;
}

export interface AgencyPayment {
  id: number;
  agency_subscription_id: number | null;
  amount: number;
  currency: string;
  payment_method: string;
  transaction_id: string | null;
  gateway: string | null;
  status: 'pending' | 'completed' | 'failed' | 'refunded';
  paid_at: string | null;
  notes: string | null;
  created_at: string;
  agency_subscription?: {
    id: number;
    plan: { name_ar: string; name_en: string } | null;
  } | null;
}

export interface CommissionReport {
  year: number;
  monthly: Array<{
    month: number;
    deal_count: number;
    total_volume: number;
    total_commission: number;
  }>;
  totals: {
    total_deals: number;
    total_volume: number;
    total_commission: number;
  };
  rate: number;
}

export async function fetchAgencyStats(): Promise<AgencyStats> {
  const res = await client.get('/agency/dashboard/stats');
  return res.data.data;
}

export async function fetchAgencyProperties(params?: Record<string, string>): Promise<{ data: PropertyCard[]; meta: any }> {
  const res = await client.get('/agency/properties', { params });
  return res.data;
}

export async function fetchAgencyProperty(id: number): Promise<any> {
  const res = await client.get(`/agency/properties/${id}`);
  return res.data.data;
}

export async function storeAgencyProperty(data: any): Promise<any> {
  const res = await client.post('/agency/properties', data);
  return res.data.data;
}

export async function updateAgencyProperty(id: number, data: any): Promise<any> {
  const res = await client.put(`/agency/properties/${id}`, data);
  return res.data.data;
}

export async function fetchAgencyAgents(): Promise<AgencyAgent[]> {
  const res = await client.get('/agency/agents');
  return res.data.data;
}

export async function storeAgencyAgent(data: any): Promise<AgencyAgent> {
  const res = await client.post('/agency/agents', data);
  return res.data.data;
}

export async function fetchAgencyInquiries(params?: Record<string, string>): Promise<{ data: AgencyInquiry[]; meta: any }> {
  const res = await client.get('/agency/inquiries', { params });
  return res.data;
}

export async function updateAgencyInquiry(id: number, status: string): Promise<any> {
  const res = await client.put(`/agency/inquiries/${id}`, { status });
  return res.data.data;
}

export async function fetchAgencySubscription(): Promise<AgencySubscription> {
  const res = await client.get('/agency/subscription');
  return res.data.data;
}

export async function subscribeToPlan(planId: number): Promise<any> {
  const res = await client.post('/agency/subscription/subscribe', { plan_id: planId });
  return res.data.data;
}

export async function fetchSakkAccount(): Promise<SakkAccount> {
  const res = await client.get('/agency/sakk-account');
  return res.data.data;
}

export async function updateSakkAccount(merchantId: string, apiKey: string): Promise<void> {
  await client.post('/agency/sakk-account', { sakk_merchant_id: merchantId, sakk_api_key: apiKey });
}

export async function removeSakkAccount(): Promise<void> {
  await client.delete('/agency/sakk-account');
}

export async function fetchAgencyProfile(): Promise<AgencyProfile> {
  const res = await client.get('/agency/profile');
  return res.data.data;
}

export async function updateAgencyProfile(data: any): Promise<AgencyProfile> {
  const res = await client.put('/agency/profile', data);
  return res.data.data;
}

export async function uploadAgencyLogo(file: File): Promise<string> {
  const formData = new FormData();
  formData.append('logo', file);
  const res = await client.post('/agency/logo', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return res.data.data.logo_url;
}

export async function uploadAgencyCover(file: File): Promise<string> {
  const formData = new FormData();
  formData.append('cover', file);
  const res = await client.post('/agency/cover', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return res.data.data.cover_url;
}

export async function fetchAgencyDeals(params?: Record<string, string>): Promise<{ data: AgencyDeal[]; meta: any }> {
  const res = await client.get('/agency/deals', { params });
  return res.data;
}

export async function storeAgencyDeal(data: any): Promise<AgencyDeal> {
  const res = await client.post('/agency/deals', data);
  return res.data.data;
}

export async function updateAgencyDeal(id: number, data: any): Promise<AgencyDeal> {
  const res = await client.put(`/agency/deals/${id}`, data);
  return res.data.data;
}

export async function fetchAgencyPayments(params?: Record<string, string>): Promise<{ data: AgencyPayment[]; meta: any }> {
  const res = await client.get('/agency/payments', { params });
  return res.data;
}

export async function fetchCommissionReport(year?: number): Promise<CommissionReport> {
  const res = await client.get('/agency/commission-report', { params: { year: year || new Date().getFullYear() } });
  return res.data.data;
}

// Chat payment request
export async function sendPaymentRequest(convId: number, data: {
  amount: number;
  currency: string;
  escrow_type: 'sale' | 'rent' | 'rental_operation';
  note?: string;
}): Promise<any> {
  const res = await client.post(`/agency/conversations/${convId}/payment-request`, data);
  return res.data.data;
}

// ── Offer / Negotiation (Agency) ──

export async function sendAgencyOffer(convId: number, data: { amount: number; currency: string; note?: string }): Promise<any> {
  const res = await client.post(`/agency/conversations/${convId}/offer`, data);
  return res.data.data;
}

export async function acceptAgencyOffer(convId: number, messageId: number): Promise<any> {
  const res = await client.post(`/agency/conversations/${convId}/offer/accept`, { message_id: messageId });
  return res.data.data;
}

export async function rejectAgencyOffer(convId: number, messageId: number, reason?: string): Promise<any> {
  const res = await client.post(`/agency/conversations/${convId}/offer/reject`, { message_id: messageId, reason });
  return res.data.data;
}

export async function counterAgencyOffer(convId: number, messageId: number, data: { amount: number; currency: string; note?: string }): Promise<any> {
  const res = await client.post(`/agency/conversations/${convId}/offer/counter`, { message_id: messageId, ...data });
  return res.data.data;
}
