import { SessionOptions } from 'iron-session';
import { IS_SECURED } from '@/lib/cookies';

const SESSION_PASSWORD: string = process.env.SESSION_SEAL_PASSWORD;

export interface SessionData {
    isLoggedIn: boolean
    userId: number | undefined
    fullName: string
    token: string | null
    customerTypeId: number | undefined
    accessHash: string
}

export const defaultSession: SessionData = {
    isLoggedIn: false,
    userId: undefined,
    fullName: '',
    token: '',
    customerTypeId: undefined,
    accessHash: '',
};

export const sessionOptions: SessionOptions = {
    password: SESSION_PASSWORD,
    cookieName: 'blockids-config',
    cookieOptions: {
        httpOnly: true,
        sameSite: 'lax', // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite#lax
        path: '/',
        // secure only works in `https` environments
        // if your localhost is not on `https`, then use: `secure: process.env.NODE_ENV === "production"`
        secure: IS_SECURED,
    },
};
