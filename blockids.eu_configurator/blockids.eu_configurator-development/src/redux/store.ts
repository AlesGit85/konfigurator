import { configureStore, EnhancedStore } from '@reduxjs/toolkit';
import configuratorReducer from '@/redux/slices/configuratorSlice';

export const store: EnhancedStore = configureStore({
    reducer: {
        configurator: configuratorReducer,
    },
    devTools: process.env.NODE_ENV !== 'production',
});

// Infer the `RootState` and `AppDispatch` types from the store itself
export type RootState = ReturnType<typeof store.getState>
// Inferred type: { posts: PostsState, comments: CommentsState, users: UsersState }
export type AppDispatch = typeof store.dispatch

