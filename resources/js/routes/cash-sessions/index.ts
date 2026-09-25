import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\CashSessionController::index
 * @see app/Http/Controllers/CashSessionController.php:17
 * @route '/cash-sessions'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cash-sessions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CashSessionController::index
 * @see app/Http/Controllers/CashSessionController.php:17
 * @route '/cash-sessions'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CashSessionController::index
 * @see app/Http/Controllers/CashSessionController.php:17
 * @route '/cash-sessions'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CashSessionController::index
 * @see app/Http/Controllers/CashSessionController.php:17
 * @route '/cash-sessions'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CashSessionController::index
 * @see app/Http/Controllers/CashSessionController.php:17
 * @route '/cash-sessions'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CashSessionController::index
 * @see app/Http/Controllers/CashSessionController.php:17
 * @route '/cash-sessions'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CashSessionController::index
 * @see app/Http/Controllers/CashSessionController.php:17
 * @route '/cash-sessions'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
/**
* @see \App\Http\Controllers\CashSessionController::open
 * @see app/Http/Controllers/CashSessionController.php:39
 * @route '/cash-sessions/open'
 */
export const open = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: open.url(options),
    method: 'post',
})

open.definition = {
    methods: ["post"],
    url: '/cash-sessions/open',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CashSessionController::open
 * @see app/Http/Controllers/CashSessionController.php:39
 * @route '/cash-sessions/open'
 */
open.url = (options?: RouteQueryOptions) => {
    return open.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CashSessionController::open
 * @see app/Http/Controllers/CashSessionController.php:39
 * @route '/cash-sessions/open'
 */
open.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: open.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CashSessionController::open
 * @see app/Http/Controllers/CashSessionController.php:39
 * @route '/cash-sessions/open'
 */
    const openForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: open.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CashSessionController::open
 * @see app/Http/Controllers/CashSessionController.php:39
 * @route '/cash-sessions/open'
 */
        openForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: open.url(options),
            method: 'post',
        })
    
    open.form = openForm
/**
* @see \App\Http\Controllers\CashSessionController::close
 * @see app/Http/Controllers/CashSessionController.php:57
 * @route '/cash-sessions/{cashSession}/close'
 */
export const close = (args: { cashSession: number | { id: number } } | [cashSession: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

close.definition = {
    methods: ["post"],
    url: '/cash-sessions/{cashSession}/close',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CashSessionController::close
 * @see app/Http/Controllers/CashSessionController.php:57
 * @route '/cash-sessions/{cashSession}/close'
 */
close.url = (args: { cashSession: number | { id: number } } | [cashSession: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { cashSession: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { cashSession: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    cashSession: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        cashSession: typeof args.cashSession === 'object'
                ? args.cashSession.id
                : args.cashSession,
                }

    return close.definition.url
            .replace('{cashSession}', parsedArgs.cashSession.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CashSessionController::close
 * @see app/Http/Controllers/CashSessionController.php:57
 * @route '/cash-sessions/{cashSession}/close'
 */
close.post = (args: { cashSession: number | { id: number } } | [cashSession: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CashSessionController::close
 * @see app/Http/Controllers/CashSessionController.php:57
 * @route '/cash-sessions/{cashSession}/close'
 */
    const closeForm = (args: { cashSession: number | { id: number } } | [cashSession: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: close.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CashSessionController::close
 * @see app/Http/Controllers/CashSessionController.php:57
 * @route '/cash-sessions/{cashSession}/close'
 */
        closeForm.post = (args: { cashSession: number | { id: number } } | [cashSession: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: close.url(args, options),
            method: 'post',
        })
    
    close.form = closeForm
const cashSessions = {
    index: Object.assign(index, index),
open: Object.assign(open, open),
close: Object.assign(close, close),
}

export default cashSessions