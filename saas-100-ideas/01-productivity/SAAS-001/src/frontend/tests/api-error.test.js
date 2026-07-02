import { describe, it, expect } from 'vitest';
import api from '@/services/api';

// Pull the real response-error handler off the axios instance and call it
// directly with fabricated error shapes — no network needed.
const rejected = api.interceptors.response.handlers[0].rejected;

describe('api error normalization', () => {
  it('surfaces Laravel validation message (422 register/login)', async () => {
    const e = {
      config: {},
      message: 'Request failed with status code 422',
      response: {
        status: 422,
        data: {
          message: 'The email has already been taken.',
          errors: { email: ['The email has already been taken.'] },
        },
      },
    };
    await expect(rejected(e)).rejects.toMatchObject({
      status: 422,
      code: 'VALIDATION_ERROR',
      message: 'The email has already been taken.',
    });
  });

  it('still reads the custom { error } envelope (429)', async () => {
    const e = {
      config: {},
      message: 'x',
      response: {
        status: 429,
        data: { error: { code: 'RATE_LIMIT_EXCEEDED', message: 'Too many requests.' } },
      },
    };
    await expect(rejected(e)).rejects.toMatchObject({
      code: 'RATE_LIMIT_EXCEEDED',
      message: 'Too many requests.',
    });
  });
});
