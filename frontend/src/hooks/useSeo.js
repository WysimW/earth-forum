import { useEffect, useState } from 'react';
import SeoHead from '../components/Seo/SeoHead';

const PRIVATE_ROUTE_PREFIXES = [
  '/login',
  '/register',
  '/tableau-de-bord',
  '/messagerie',
  '/profil',
  '/characters',
  '/mes-factions',
  '/factions',
  '/components',
  '/test',
  '/rp-activities/new',
];

export const isPrivateRoute = (pathname) => {
  if (!pathname) return false;
  return PRIVATE_ROUTE_PREFIXES.some((prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`));
};

export const useSeoLoader = (loader, deps = []) => {
  const [seo, setSeo] = useState(null);

  useEffect(() => {
    let mounted = true;

    const load = async () => {
      try {
        const data = await loader();
        if (mounted) {
          setSeo(data || null);
        }
      } catch (error) {
        if (mounted) {
          setSeo(null);
        }
      }
    };

    load();

    return () => {
      mounted = false;
    };
  }, deps);

  return seo;
};

export const useRouteSeoGuard = (pathname) => {
  const noindexSeo = {
    metaTitle: 'Earth Forum',
    metaDescription: '',
    robotsIndex: false,
  };

  if (isPrivateRoute(pathname)) {
    return noindexSeo;
  }

  return null;
};

export { SeoHead };
