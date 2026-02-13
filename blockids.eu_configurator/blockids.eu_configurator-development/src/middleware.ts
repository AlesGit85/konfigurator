import { NextRequest, NextResponse } from 'next/server';
import { i18nRouter } from 'next-i18n-router';
import { getSession } from '@/action/session/getSessionAction';
import i18nConfig from '@/i18nConfig';

export async function middleware(request: NextRequest) {
    const cookieSession = await getSession();

    if (!cookieSession?.isLoggedIn) {
        if (!request.nextUrl.pathname.includes('/sso') && !request.nextUrl.pathname.includes('/nahled')) {
            return NextResponse.redirect(new URL('/sso', request.url));
        }
    }

    return i18nRouter(request, i18nConfig);
}

// applies this middleware only to files in the app directory
export const config = {
    matcher: '/((?!api|static|.*\\..*|_next).*)',
};
