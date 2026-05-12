import { unsealData } from 'iron-session';
import { cookies } from 'next/headers';
import { sessionOptions } from '@/lib/session';

export const getCookie = (name: string) => cookies().get(name);
export const getCookieValue = (name: string) => getCookie(name)?.value;
export const getCookieUnsealedValue = async(name: string): Promise<string> => {
    const val: string | undefined = getCookie(name)?.value;
    if (!val) return '';
    return await unsealData(val, { password: sessionOptions.password });
};
export const deleteCookie = (name: string) => cookies().delete(name);

export const MAX_AGE = 365 * 24 * 60 * 60 * 1000;
export const IS_SECURED = process.env.NODE_ENV === 'production';
