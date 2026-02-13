'use client';

import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { loginByTokenAction } from '@/action/session/loginByTokenAction';
import headerClasses from '@/components/header/header.module.scss';
import Spinner from '@/components/spinner/spinner';
import LogoSvg from '/public/logo.svg';
import classes from '../sso.module.scss';

export interface ISignInProps {
    token: string
}

const SignIn = (
    {
        token,
    }: ISignInProps,
) => {
    const { t } = useTranslation();

    useEffect(() => {
        const login = async() => {
            if (token) {
                try {
                    await loginByTokenAction(token);
                } catch (e) {
                    // REDIRECT DO PRYC?
                    console.error(e);
                }
            }
        };

        login();
    }, [token]);

    return (
        <div className={classes.root}>
            <Spinner
                className={classes.container}
                title={t('common:loading')}
            >
                <div className={classes.brand}>
                    <LogoSvg/>
                    <h1 className={headerClasses.logo}>
                        <span className={headerClasses.title}>
                            {t('header:appTitle')}
                        </span>
                    </h1>
                </div>
            </Spinner>
        </div>
    );
};

export default SignIn;
