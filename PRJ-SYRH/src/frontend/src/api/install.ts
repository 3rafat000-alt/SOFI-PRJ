import client from './client';

export interface RequirementCheck {
  name: string;
  passed: boolean;
  value?: string;
}

export interface RequirementsResponse {
  data: {
    checks: RequirementCheck[];
    allPassed: boolean;
  };
}

export interface DatabaseConfigResponse {
  data: {
    current: string;
    drivers: string[];
  };
}

export interface SettingsDefaults {
  app_name: string;
  app_url: string;
  currency: string;
}

export interface CompleteData {
  installed_at: string | null;
  admin_email: string;
  app_name: string;
  app_url: string;
}

// Step 1
export function fetchRequirements(): Promise<RequirementsResponse> {
  return client.get('/install/requirements').then(r => r.data);
}

// Step 2
export function fetchDatabaseConfig(): Promise<DatabaseConfigResponse> {
  return client.get('/install/database').then(r => r.data);
}

export function saveDatabaseConfig(data: {
  driver: string;
  host?: string;
  port?: string;
  name?: string;
  user?: string;
  password?: string;
}): Promise<{ success: boolean; message: string }> {
  return client.post('/install/database', data).then(r => r.data);
}

// Step 3
export function saveAdmin(data: {
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
}): Promise<{ success: boolean; message: string }> {
  return client.post('/install/admin', data).then(r => r.data);
}

// Step 4
export function fetchSettings(): Promise<{ data: { defaults: SettingsDefaults } }> {
  return client.get('/install/settings').then(r => r.data);
}

export function saveSettings(data: {
  app_name: string;
  app_url: string;
  currency: string;
}): Promise<{ success: boolean; message: string }> {
  return client.post('/install/settings', data).then(r => r.data);
}

// Step 5
export function fetchComplete(): Promise<{ data: CompleteData }> {
  return client.get('/install/complete').then(r => r.data);
}
