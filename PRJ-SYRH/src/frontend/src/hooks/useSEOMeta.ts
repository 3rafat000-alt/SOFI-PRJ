import { useEffect, useRef } from 'react';

interface SEOData {
  title: string;
  description: string;
  image?: string | null;
  url?: string;
  type?: 'website' | 'article' | 'product';
  schema?: Record<string, unknown> | null;
}

/**
 * Update document title, meta tags, OG tags, and inject JSON-LD schema.
 * Cleans up on unmount or re-render with new data.
 */
export default function useSEOMeta(data: SEOData | null) {
  const prevRef = useRef<string>('');

  useEffect(() => {
    // Serialize to compare — skip if same as previous
    const key = JSON.stringify(data);
    if (key === prevRef.current) return;
    prevRef.current = key;

    // Clean previous schema script
    const oldScript = document.getElementById('sofi-jsonld');
    oldScript?.remove();

    if (!data) return;

    // ── Title ──
    document.title = data.title;

    // ── Meta tags ──
    const setMeta = (name: string, content: string, property = false) => {
      const attr = property ? 'property' : 'name';
      const selector = `meta[${attr}="${name}"]`;
      let el = document.querySelector(selector) as HTMLMetaElement | null;
      if (!el) {
        el = document.createElement('meta');
        el.setAttribute(attr, name);
        document.head.appendChild(el);
      }
      el.setAttribute('content', content);
    };

    // Basic meta
    setMeta('description', data.description);

    // Open Graph
    setMeta('og:title', data.title, true);
    setMeta('og:description', data.description, true);
    setMeta('og:type', data.type || 'website', true);
    setMeta('og:url', data.url || window.location.href, true);
    if (data.image) {
      setMeta('og:image', data.image, true);
      setMeta('og:image:width', '1200', true);
      setMeta('og:image:height', '630', true);
    }

    // Twitter Card
    setMeta('twitter:card', 'summary_large_image');
    setMeta('twitter:title', data.title);
    setMeta('twitter:description', data.description);
    if (data.image) {
      setMeta('twitter:image', data.image);
    }

    // ── JSON-LD Schema ──
    if (data.schema) {
      const script = document.createElement('script');
      script.id = 'sofi-jsonld';
      script.type = 'application/ld+json';
      script.textContent = JSON.stringify(data.schema);
      document.head.appendChild(script);
    }
  }, [data]);
}
