import client from './client';
import type { Governorate, Area } from './client';

export interface LocationsResponse {
  governorates: Governorate[];
  popular_areas: Area[];
}

export async function fetchLocations(): Promise<LocationsResponse> {
  const { data } = await client.get('/locations');
  return data.data;
}

export async function fetchAreas(governorateSlug: string): Promise<Area[]> {
  const { data } = await client.get(`/locations/${governorateSlug}/areas`);
  return data.data ?? [];
}
