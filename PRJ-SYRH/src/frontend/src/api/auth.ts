import client from './client';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  locale: string;
  avatar_url: string | null;
  roles: { id: number; name: string }[];
}

export interface AuthResponse {
  data: {
    user: AuthUser;
    token: string;
  };
}

export async function login(email: string, password: string): Promise<AuthResponse> {
  const res = await client.post('/auth/login', { email, password });
  return res.data;
}

export interface AgencyRegisterData {
  owner_name: string;
  owner_email: string;
  owner_phone: string;
  password: string;
  password_confirmation: string;
  agency_name: string;
  license_no?: string;
  agency_email?: string;
  agency_phone?: string;
  whatsapp?: string;
  address?: string;
  locale?: string;
}

export interface AgencyRegisterResponse {
  data: {
    user: AuthUser;
    agency: {
      id: number;
      name: string;
      slug: string;
      license_no: string | null;
      status: string;
    };
    token: string;
  };
}

export async function agencyRegister(data: AgencyRegisterData): Promise<AgencyRegisterResponse> {
  const res = await client.post('/auth/agency-register', data);
  return res.data;
}

export async function register(data: {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
  locale?: string;
}): Promise<AuthResponse> {
  const res = await client.post('/auth/register', data);
  return res.data;
}

export async function logout(): Promise<void> {
  await client.post('/auth/logout');
}

export async function fetchMe(): Promise<AuthUser> {
  const res = await client.get('/auth/me');
  return res.data.data;
}

export async function forgotPassword(email: string): Promise<string> {
  const res = await client.post('/auth/forgot-password', { email });
  return res.data.message;
}

export async function resetPassword(data: {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}): Promise<string> {
  const res = await client.post('/auth/reset-password', data);
  return res.data.message;
}

export async function updateProfile(data: { name?: string; phone?: string; locale?: string }): Promise<AuthUser> {
  const res = await client.put('/auth/profile', data);
  return res.data.data;
}

export async function uploadAvatar(file: File): Promise<AuthUser> {
  const form = new FormData();
  form.append('avatar', file);
  const res = await client.post('/auth/avatar', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return res.data.data;
}

export async function changePassword(currentPassword: string, newPassword: string): Promise<string> {
  const res = await client.post('/auth/change-password', {
    current_password: currentPassword,
    password: newPassword,
    password_confirmation: newPassword,
  });
  return res.data.message;
}

export interface FavoriteItem {
  id: number;
  property: PropertyCard;
  created_at: string;
}

import type { PropertyCard } from './client';

export async function fetchFavorites(): Promise<FavoriteItem[]> {
  const res = await client.get('/favorites');
  return res.data.data;
}

export async function toggleFavorite(propertyId: number): Promise<{ favorited: boolean }> {
  const res = await client.post('/favorites/toggle', { property_id: propertyId });
  return res.data.data;
}

export interface SavedSearchItem {
  id: number;
  name: string;
  filters: string;
  created_at: string;
}

export async function fetchSavedSearches(): Promise<SavedSearchItem[]> {
  const res = await client.get('/saved-searches');
  return res.data.data;
}

export async function saveSearch(name: string, filters: Record<string, any>): Promise<SavedSearchItem> {
  const res = await client.post('/saved-searches', { name, filters: JSON.stringify(filters) });
  return res.data.data;
}

export async function deleteSavedSearch(id: number): Promise<void> {
  await client.delete(`/saved-searches/${id}`);
}
