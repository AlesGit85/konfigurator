/** @type {import('next').NextConfig} */

const path = require('path');
const loaderUtils = require('loader-utils');

const isProd = process.env.NODE_ENV === 'production';

const cssModuleLocalIdent = (context, _, exportName, options) => {
    const relativePath = path.relative(context.rootContext, context.resourcePath).replace(/\\+/g, '/');
    const hash = loaderUtils.getHashDigest(Buffer.from(`filePath:${relativePath}#className:${exportName}`), 'sha1', 'base64', 20);
    return loaderUtils.interpolateName(context, hash, options).replace(
        /\.module_/, '_')
        .replace(/[^a-zA-Z0-9-_]/g, '_')
        .replace(/^(\d|--|-\d)/, '__$1');
};

module.exports = {
    images: {
        remotePatterns: [
            {
                protocol: 'https',
                hostname: 'phpstack-851454-4637763.cloudwaysapps.com',
                port: '',
                pathname: '/**',
            },
            {
                protocol: 'http',
                hostname: 'blockids.eu.localhost',
                port: '',
                pathname: '/**',
            },
            {
                protocol: 'https',
                hostname: 'blockids.creaticom.cz',
                port: '',
                pathname: '/**',
            },
        ],
    },
    serverRuntimeConfig: {
        allowedLocales: ['cs'],
    },
    compiler: {
        removeConsole: process.env.NODE_ENV === 'production',
    },
    webpack(config, { dev }) {
        config.module.rules.push({
            test: /\.svg$/,
            exclude: /favicon\.svg|icon\.svg$/,
            use: ['@svgr/webpack'],
        });

        const rules = config.module.rules
            .find((rule) => typeof rule.oneOf === 'object')
            .oneOf.filter((rule) => Array.isArray(rule.use));

        if (isProd) {
            rules.forEach((rule) => {
                rule.use.forEach((moduleLoader) => {
                    if (
                        moduleLoader.loader?.includes('css-loader')
                        && !moduleLoader.loader?.includes('postcss-loader')
                        && moduleLoader.options.modules
                    ) {
                        //moduleLoader.options.modules.getLocalIdent = cssModuleLocalIdent;
                    }
                });
            });
        }

        return config;
    },
    eslint: {
        // Warning: This allows production builds to successfully complete even if
        // your project has ESLint errors.
        ignoreDuringBuilds: true,
    },
    typescript: {
        // !! WARN !!
        // Dangerously allow production builds to successfully complete even if
        // your project has type errors.
        // !! WARN !!
        ignoreBuildErrors: true,
    },
};

