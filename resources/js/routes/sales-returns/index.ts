import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SalesReturnController::index
 * @see app/Http/Controllers/SalesReturnController.php:17
 * @route '/sales-returns'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/sales-returns',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SalesReturnController::index
 * @see app/Http/Controllers/SalesReturnController.php:17
 * @route '/sales-returns'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SalesReturnController::index
 * @see app/Http/Controllers/SalesReturnController.php:17
 * @route '/sales-returns'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SalesReturnController::index
 * @see app/Http/Controllers/SalesReturnController.php:17
 * @route '/sales-returns'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SalesReturnController::index
 * @see app/Http/Controllers/SalesReturnController.php:17
 * @route '/sales-returns'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SalesReturnController::index
 * @see app/Http/Controllers/SalesReturnController.php:17
 * @route '/sales-returns'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SalesReturnController::index
 * @see app/Http/Controllers/SalesReturnController.php:17
 * @route '/sales-returns'
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
* @see \App\Http\Controllers\SalesReturnController::store
 * @see app/Http/Controllers/SalesReturnController.php:40
 * @route '/sales-returns'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/sales-returns',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SalesReturnController::store
 * @see app/Http/Controllers/SalesReturnController.php:40
 * @route '/sales-returns'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SalesReturnController::store
 * @see app/Http/Controllers/SalesReturnController.php:40
 * @route '/sales-returns'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\SalesReturnController::store
 * @see app/Http/Controllers/SalesReturnController.php:40
 * @route '/sales-returns'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\SalesReturnController::store
 * @see app/Http/Controllers/SalesReturnController.php:40
 * @route '/sales-returns'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
const salesReturns = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
}

export default salesReturns