<template>

    <Head title="Documentations" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-black dark:via-black dark:to-black">
            <div class="container mx-auto px-4 py-6 sm:py-8 max-w-5xl">
                <!-- Header Section -->
                <div class="text-center mb-6 px-4">
                    <div
                        class="inline-flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full mb-4">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                            </path>
                        </svg>
                    </div>
                    <h1
                        class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-1 leading-tight">
                        <span class="block sm:inline creative-studio-font mr-2">CREATIVE STUDIO</span>
                        <span
                            class="block sm:inline bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Documentation</span>
                    </h1>
                    <p class="text-base sm:text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed">
                        Everything you need to know about our preview system, from basics to advanced features.
                    </p>
                    <div class="mt-4 flex justify-center">
                        <a href="https://github.com/govindaroyaiub/creative-vuejs-laravel" target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors shadow-sm">
                            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M12 .5C5.648.5.5 5.648.5 12c0 5.097 3.292 9.418 7.86 10.944.575.105.784-.25.784-.555 0-.275-.01-1.007-.016-1.978-3.197.696-3.873-1.542-3.873-1.542-.522-1.33-1.276-1.684-1.276-1.684-1.043-.713.08-.699.08-.699 1.154.081 1.761 1.184 1.761 1.184 1.025 1.756 2.689 1.25 3.345.957.104-.744.401-1.25.73-1.538-2.553-.29-5.236-1.277-5.236-5.682 0-1.255.45-2.281 1.185-3.087-.119-.29-.513-1.458.112-3.04 0 0 .966-.31 3.166 1.18a11.01 11.01 0 012.882-.388c.978.004 1.964.131 2.882.388 2.197-1.49 3.162-1.18 3.162-1.18.628 1.582.234 2.75.116 3.04.737.806 1.183 1.832 1.183 3.087 0 4.415-2.688 5.388-5.251 5.674.412.355.78 1.054.78 2.126 0 1.536-.014 2.775-.014 3.152 0 .308.206.666.79.553C20.71 21.416 24 17.095 24 12c0-6.352-5.148-11.5-12-11.5z" />
                            </svg>
                            View on GitHub
                        </a>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div
                    class="flex flex-col sm:flex-row mb-8 bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-2 gap-2 sm:gap-0">
                    <button @click="activeTab = 'qa'" :class="[
                        'flex-1 px-4 sm:px-6 py-3 rounded-lg font-medium transition-all duration-200 text-sm sm:text-base sm:mr-1',
                        activeTab === 'qa'
                            ? 'bg-blue-600 text-white shadow-lg'
                            : 'text-gray-600 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-neutral-700'
                    ]">
                        <span class="hidden sm:inline">📚 Q&A Documentation</span>
                        <span class="sm:hidden">📚 Q&A</span>
                    </button>
                    <button @click="activeTab = 'api'" :class="[
                        'flex-1 px-4 sm:px-6 py-3 rounded-lg font-medium transition-all duration-200 text-sm sm:text-base sm:ml-1',
                        activeTab === 'api'
                            ? 'bg-green-600 text-white shadow-lg'
                            : 'text-gray-600 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-neutral-700'
                    ]">
                        <span class="hidden sm:inline">🚀 API Documentation</span>
                        <span class="sm:hidden">🚀 API</span>
                    </button>
                </div>

                <!-- Search Section -->
                <div class="relative mb-4" v-show="activeTab === 'qa'">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Search documentation..."
                        class="block w-full pl-10 pr-4 py-4 text-lg border-2 border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 shadow-sm hover:shadow-md" />
                    <div v-if="search" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button @click="search = ''" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- API Search Section -->
                <div class="relative mb-4" v-show="activeTab === 'api'">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input v-model="apiSearch" type="text" placeholder="Search API endpoints, methods, categories..."
                        class="block w-full pl-10 pr-4 py-4 text-lg border-2 border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 shadow-sm hover:shadow-md" />
                    <div v-if="apiSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button @click="apiSearch = ''"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Q&A Documentation Content -->
                <div v-show="activeTab === 'qa'">
                    <!-- Q/A Cards -->
                    <div class="space-y-4">
                        <div v-for="(qa, idx) in filteredQA" :key="idx" :id="'qa-' + idx"
                            class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 overflow-hidden transition-all duration-200 hover:shadow-lg hover:border-blue-200 dark:hover:border-blue-700">
                            <button @click="toggle(idx)"
                                class="w-full text-left px-4 sm:px-6 py-4 sm:py-5 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:ring-inset group">
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex items-start space-x-3 sm:space-x-4 flex-1">
                                        <div
                                            class="flex-shrink-0 w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 rounded-lg flex items-center justify-center group-hover:from-blue-200 group-hover:to-indigo-200 dark:group-hover:from-blue-800 dark:group-hover:to-indigo-800 transition-colors">
                                            <span
                                                class="text-blue-600 dark:text-blue-400 font-bold text-sm sm:text-lg">{{
                                                    idx + 1
                                                }}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3
                                                class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
                                                {{ qa.question }}
                                            </h3>
                                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">Click to
                                                {{ openIdx
                                                    ===
                                                    idx ? 'collapse' : 'expand' }} answer</p>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-6 h-6 sm:w-8 sm:h-8 bg-gray-100 dark:bg-neutral-800 rounded-full flex items-center justify-center group-hover:bg-blue-100 dark:group-hover:bg-blue-900 transition-colors">
                                            <svg :class="['w-3 h-3 sm:w-4 sm:h-4 text-gray-600 dark:text-gray-400 transition-transform duration-200', openIdx === idx ? 'rotate-180' : '']"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </button>

                            <transition name="slide-fade">
                                <div v-if="openIdx === idx" class="border-t border-gray-200 dark:border-neutral-700">
                                    <div class="px-4 sm:px-6 py-4 sm:py-6 bg-gray-50 dark:bg-neutral-900/50">
                                        <div
                                            class="prose prose-gray dark:prose-invert max-w-none prose-sm sm:prose-base">
                                            <div v-html="qa.answer"
                                                class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4"></div>
                                            <div v-if="qa.additionalInfo"
                                                class="mt-4 sm:mt-6 p-3 sm:p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border-l-4 shadow border-blue-400">
                                                <h4
                                                    class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                        </path>
                                                    </svg>
                                                    Additional Information
                                                </h4>
                                                <div v-html="qa.additionalInfo"
                                                    class="text-gray-700 dark:text-gray-300 text-sm"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>

                    <!-- No Results -->
                    <div v-if="filteredQA.length === 0" class="text-center py-16">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-neutral-800 rounded-full mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 20a7.962 7.962 0 01-5.207-1.709l-4.086 4.086a1 1 0 01-1.414-1.414l4.086-4.086A7.962 7.962 0 014 12a8 8 0 018-8c.322 0 .64.019.954.057">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No questions found</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">Try searching with different keywords</p>
                        <button @click="search = ''"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            Clear Search
                        </button>
                    </div>
                </div>

                <!-- API Documentation Content -->
                <div v-show="activeTab === 'api'" class="space-y-8">
                    <!-- API Introduction -->
                    <div
                        class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-8">
                        <div class="flex items-center mb-6">
                            <span
                                class="w-8 h-8 bg-green-600 text-white rounded-lg flex items-center justify-center text-lg font-bold mr-3">🚀</span>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Complete API Reference</h2>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-lg mb-6">
                            Comprehensive documentation for all Creative Studio Planet Nine endpoints. This covers every
                            route
                            in the application including authentication, permissions, and expected parameters.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-2">Base URL</h4>
                                <code class="text-sm text-blue-700 dark:text-blue-300 break-all">{{ baseUrl }}</code>
                            </div>
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <h4 class="font-semibold text-green-900 dark:text-green-200 mb-2">Authentication</h4>
                                <code class="text-sm text-green-700 dark:text-green-300">Session-based</code>
                            </div>
                            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <h4 class="font-semibold text-purple-900 dark:text-purple-200 mb-2">Total Routes</h4>
                                <code class="text-sm text-purple-700 dark:text-purple-300">100+ Endpoints</code>
                            </div>
                            <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                <h4 class="font-semibold text-orange-900 dark:text-orange-200 mb-2">Security</h4>
                                <code class="text-sm text-orange-700 dark:text-orange-300">CSRF + Permissions</code>
                            </div>
                        </div>
                    </div>

                    <!-- Search Results Counter -->
                    <div v-if="apiSearch" class="mb-6 text-center">
                        <div
                            class="inline-flex items-center px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-lg">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium">
                                Found {{ Object.values(filteredApiEndpoints).flat().length }}
                                {{ Object.values(filteredApiEndpoints).flat().length === 1 ? 'endpoint' : 'endpoints' }}
                                in {{ Object.keys(filteredApiEndpoints).length }}
                                {{ Object.keys(filteredApiEndpoints).length === 1 ? 'category' : 'categories' }}
                                for "{{ apiSearch }}"
                            </span>
                        </div>
                    </div>

                    <!-- Endpoint Categories -->
                    <div v-for="(endpoints, category) in filteredApiEndpoints" :key="category"
                        class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-8">
                        <div class="flex items-center mb-6">
                            <span
                                class="w-8 h-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center text-lg font-bold mr-3">
                                {{ getCategoryIcon(category) }}
                            </span>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCategoryName(category)
                            }}</h2>
                        </div>

                        <div class="space-y-4">
                            <div v-for="endpoint in endpoints" :key="endpoint.path"
                                class="border border-gray-200 dark:border-neutral-600 rounded-lg p-3 sm:p-4 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 gap-2">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                        <span :class="getMethodBadgeClass(endpoint.method)"
                                            class="px-3 py-1 rounded text-xs font-bold w-fit">
                                            {{ endpoint.method }}
                                        </span>
                                        <code
                                            class="text-sm font-mono text-gray-700 dark:text-gray-300 break-all">{{ endpoint.path }}</code>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        <span v-if="endpoint.authenticated"
                                            class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200 rounded whitespace-nowrap">
                                            🔒 Auth Required
                                        </span>
                                        <span v-if="(endpoint as any).permission"
                                            class="px-2 py-1 bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-200 rounded whitespace-nowrap">
                                            📋 {{ (endpoint as any).permission }}
                                        </span>
                                        <span v-if="(endpoint as any).middleware"
                                            class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200 rounded whitespace-nowrap">
                                            🛡️ {{ (endpoint as any).middleware }}
                                        </span>
                                    </div>
                                </div>

                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ endpoint.description }}</p>

                                <div v-if="(endpoint as any).parameters" class="mb-3">
                                    <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Parameters:
                                    </h5>
                                    <code
                                        class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ (endpoint as any).parameters }}</code>
                                </div>

                                <div v-if="(endpoint as any).returnType" class="mb-3">
                                    <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Returns:
                                    </h5>
                                    <code
                                        class="text-xs bg-green-100 dark:bg-green-700 px-2 py-1 rounded">{{ (endpoint as any).returnType }}</code>
                                </div>

                                <div v-if="(endpoint as any).rateLimit" class="mb-3">
                                    <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Rate Limit:
                                    </h5>
                                    <code
                                        class="text-xs bg-orange-100 dark:bg-orange-700 px-2 py-1 rounded">{{ (endpoint as any).rateLimit }}</code>
                                </div>

                                <div v-if="(endpoint as any).note"
                                    class="mb-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                                    <p class="text-xs text-blue-700 dark:text-blue-300">💡 {{ (endpoint as any).note }}
                                    </p>
                                </div>

                                <div v-if="(endpoint as any).example" class="mt-3">
                                    <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Example
                                        Response:</h5>
                                    <pre
                                        class="text-xs bg-slate-900 text-slate-300 p-3 rounded overflow-x-auto whitespace-pre-wrap break-words"><code>{{ (endpoint as any).example }}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Results Message for API Search -->
                    <div v-if="apiSearch && Object.keys(filteredApiEndpoints).length === 0"
                        class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-8 text-center">
                        <div class="flex flex-col items-center">
                            <div
                                class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.485 0-4.735.974-6.369 2.567" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No API endpoints found
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 max-w-md">
                                No endpoints match your search for "<strong>{{ apiSearch }}</strong>".
                                Try searching for different terms like method types (GET, POST), endpoint paths, or
                                categories.
                            </p>
                            <button @click="apiSearch = ''"
                                class="mt-4 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                Clear Search
                            </button>
                        </div>
                    </div>

                    <!-- Authentication Guide -->
                    <div
                        class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-8">
                        <div class="flex items-center mb-6">
                            <span
                                class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center text-lg font-bold mr-3">🔑</span>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Authentication & Security</h2>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Session
                                    Authentication</h3>
                                <p class="text-gray-600 dark:text-gray-300 mb-4">
                                    Most endpoints require authentication via Laravel's session system. Users must be
                                    logged in through the web interface.
                                </p>
                                <div class="bg-gray-50 dark:bg-neutral-900 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Login Process:</h4>
                                    <pre class="text-sm text-gray-600 dark:text-gray-300 overflow-x-auto"><code>// 1. Get CSRF token
await axios.get('/sanctum/csrf-cookie');

// 2. Login
await axios.post('/login', {
    email: 'user@example.com',
    password: 'password'
});

// 3. Make authenticated requests
const response = await axios.get('/dashboard');</code></pre>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Permission System
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300 mb-4">
                                    Each user has specific route permissions. Routes are protected by the
                                    CheckUserPermission middleware.
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                        <h4 class="font-semibold text-green-900 dark:text-green-200 mb-2">Admin Access
                                        </h4>
                                        <p class="text-sm text-green-700 dark:text-green-300">Users with '*' permission
                                            can access all routes</p>
                                    </div>
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-2">Route-Specific
                                        </h4>
                                        <p class="text-sm text-blue-700 dark:text-blue-300">Users need specific route
                                            permissions like '/previews', '/clients'</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Security Features
                                </h3>
                                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                                    <li class="flex items-center">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                                        CSRF protection on all POST/PUT/DELETE requests
                                    </li>
                                    <li class="flex items-center">
                                        <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                                        Rate limiting on authentication endpoints
                                    </li>
                                    <li class="flex items-center">
                                        <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                        Permission-based route access control
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Response Codes -->
                    <div
                        class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-8">
                        <div class="flex items-center mb-6">
                            <span
                                class="w-8 h-8 bg-yellow-600 text-white rounded-lg flex items-center justify-center text-lg font-bold mr-3">⚠️</span>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">HTTP Response Codes</h2>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-for="code in responseCodes" :key="code.code"
                                class="flex items-center p-4 rounded-lg border border-gray-200 dark:border-neutral-600">
                                <span :class="code.badgeClass" class="px-3 py-1 rounded font-bold text-sm mr-4">
                                    {{ code.code }}
                                </span>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ code.title }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ code.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-16 text-center">
                    <div
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full text-sm font-medium">
                        Made with ❤️ by Govinda Roy
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Documentations', href: '/documentations' }];

const search = ref('')
const apiSearch = ref('')
const openIdx = ref<number | null>(null)
const activeTab = ref('qa')
const baseUrl = window.location.origin

// API Documentation Data
const apiEndpoints = ref({
    dashboard: [
        {
            method: 'GET',
            path: '/dashboard',
            description: 'Dashboard overview with statistics and recent activity',
            authenticated: true,
            permission: '/dashboard'
        }
    ],
    previews: [
        {
            method: 'GET',
            path: '/previews',
            description: 'List all previews with pagination and filtering',
            authenticated: true,
            permission: '/previews',
            example: JSON.stringify({
                data: [
                    {
                        id: 1,
                        title: 'Campaign Preview',
                        slug: 'campaign-preview-123',
                        status: 'active',
                        client: { id: 1, name: 'Acme Corp' },
                        color_palette: { id: 1, name: 'Blue Theme' },
                        uploader: { name: 'John Doe' }
                    }
                ],
                meta: { total: 15, per_page: 10, current_page: 1 }
            }, null, 2)
        },
        {
            method: 'POST',
            path: '/previews-store',
            description: 'Create a new preview with initial setup',
            authenticated: true,
            permission: '/previews',
            parameters: 'title, client_id, color_palette_id, description'
        },
        {
            method: 'GET',
            path: '/previews-edit/{id}',
            description: 'Get preview details for editing',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'PUT',
            path: '/previews-edit/{id}',
            description: 'Update an existing preview',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'DELETE',
            path: '/previews-delete/{id}',
            description: 'Delete a preview and all associated content',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'GET',
            path: '/previews/update/{id}',
            description: 'Get comprehensive preview data for update interface',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'POST',
            path: '/previews/{id}/bulk-edit',
            description: 'Bulk edit multiple preview elements',
            authenticated: true,
            permission: '/previews'
        }
    ],
    previewManagement: [
        {
            method: 'DELETE',
            path: '/previews/category/delete/{id}',
            description: 'Delete a preview category',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'PUT',
            path: '/previews/feedback/approve/{id}',
            description: 'Approve feedback for a preview element',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'PUT',
            path: '/previews/feedback/disapprove/{id}',
            description: 'Disapprove feedback for a preview element',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'DELETE',
            path: '/previews/feedback/delete/{id}',
            description: 'Delete feedback entry',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'DELETE',
            path: '/previews/feedbackSet/delete/{id}',
            description: 'Delete an entire feedback set',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'DELETE',
            path: '/previews/version/delete/{id}',
            description: 'Delete a version and its assets',
            authenticated: true,
            permission: '/previews'
        }
    ],
    creativeAssets: [
        {
            method: 'POST',
            path: '/previews/banner/edit/{id}',
            description: 'Update banner creative with new file or metadata',
            authenticated: true,
            permission: '/previews',
            parameters: 'file, title, banner_size_id'
        },
        {
            method: 'DELETE',
            path: '/previews/banner/delete/{id}',
            description: 'Delete banner creative',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'GET',
            path: '/previews/banner/download/{id}',
            description: 'Download banner file',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'POST',
            path: '/previews/social/edit/{id}',
            description: 'Update social media creative',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'DELETE',
            path: '/previews/social/delete/{id}',
            description: 'Delete social media creative',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'POST',
            path: '/previews/gif/edit/{id}',
            description: 'Update GIF creative',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'DELETE',
            path: '/previews/gif/delete/{id}',
            description: 'Delete GIF creative',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'POST',
            path: '/previews/video/edit/{id}',
            description: 'Update video creative',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'DELETE',
            path: '/previews/video/delete/{id}',
            description: 'Delete video creative',
            authenticated: true,
            permission: '/previews'
        },
        {
            method: 'DELETE',
            path: '/previews/companion-banner/delete/{id}',
            description: 'Delete companion banner for video',
            authenticated: true,
            permission: '/previews'
        }
    ],
    fileTransfers: [
        {
            method: 'GET',
            path: '/file-transfers',
            description: 'List all file transfers with status and metadata',
            authenticated: true,
            permission: '/file-transfers',
            example: JSON.stringify({
                data: [
                    {
                        id: 1,
                        title: 'Project Assets Q4',
                        slug: 'project-assets-q4-xyz',
                        description: 'Design assets for Q4 campaign',
                        file_count: 15,
                        total_size: '45.2 MB',
                        status: 'active',
                        expires_at: '2025-12-06T00:00:00.000000Z'
                    }
                ]
            }, null, 2)
        },
        {
            method: 'GET',
            path: '/file-transfers-add',
            description: 'Show form to create new file transfer',
            authenticated: true,
            permission: '/file-transfers'
        },
        {
            method: 'POST',
            path: '/file-transfers-add',
            description: 'Create new file transfer',
            authenticated: true,
            permission: '/file-transfers',
            middleware: 'secure.upload',
            parameters: 'title, description, files[]'
        },
        {
            method: 'GET',
            path: '/file-transfers-edit/{id}',
            description: 'Get file transfer details for editing',
            authenticated: true,
            permission: '/file-transfers'
        },
        {
            method: 'POST',
            path: '/file-transfers-edit/{id}',
            description: 'Update file transfer',
            authenticated: true,
            permission: '/file-transfers',
            middleware: 'secure.upload'
        },
        {
            method: 'DELETE',
            path: '/file-transfers-delete/{id}',
            description: 'Delete file transfer and associated files',
            authenticated: true,
            permission: '/file-transfers'
        },
        {
            method: 'GET',
            path: '/file-transfers-view/{slug}',
            description: 'Public view of file transfer for download',
            authenticated: false
        }
    ],
    configuration: [
        {
            method: 'GET',
            path: '/banner-sizes',
            description: 'List all available banner size configurations',
            authenticated: true,
            permission: '/banner-sizes'
        },
        {
            method: 'POST',
            path: '/banner-sizes-create-post',
            description: 'Create new banner size configuration',
            authenticated: true,
            permission: '/banner-sizes',
            parameters: 'name, width, height'
        },
        {
            method: 'PUT',
            path: '/banner-sizes-edit/{id}',
            description: 'Update banner size configuration',
            authenticated: true,
            permission: '/banner-sizes'
        },
        {
            method: 'DELETE',
            path: '/banner-sizes-delete/{id}',
            description: 'Delete banner size configuration',
            authenticated: true,
            permission: '/banner-sizes'
        },
        {
            method: 'GET',
            path: '/video-sizes',
            description: 'List all video size configurations',
            authenticated: true,
            permission: '/video-sizes'
        },
        {
            method: 'POST',
            path: '/video-sizes-create-post',
            description: 'Create new video size configuration',
            authenticated: true,
            permission: '/video-sizes'
        },
        {
            method: 'PUT',
            path: '/video-sizes-edit/{id}',
            description: 'Update video size configuration',
            authenticated: true,
            permission: '/video-sizes'
        },
        {
            method: 'DELETE',
            path: '/video-sizes-delete/{id}',
            description: 'Delete video size configuration',
            authenticated: true,
            permission: '/video-sizes'
        },
        {
            method: 'GET',
            path: '/socials',
            description: 'List social media platform configurations',
            authenticated: true,
            permission: '/socials'
        },
        {
            method: 'POST',
            path: '/socials-create-post',
            description: 'Create new social platform configuration',
            authenticated: true,
            permission: '/socials'
        },
        {
            method: 'PUT',
            path: '/socials-edit/{id}',
            description: 'Update social platform configuration',
            authenticated: true,
            permission: '/socials'
        },
        {
            method: 'DELETE',
            path: '/socials-delete/{id}',
            description: 'Delete social platform configuration',
            authenticated: true,
            permission: '/socials'
        }
    ],
    clients: [
        {
            method: 'GET',
            path: '/clients',
            description: 'List all clients with their configurations',
            authenticated: true,
            permission: '/clients',
            example: JSON.stringify({
                data: [
                    {
                        id: 1,
                        name: 'Acme Corporation',
                        logo: 'uploads/logos/acme-logo.png',
                        preview_url: 'https://preview.acme.com',
                        color_palette_id: 1,
                        status: 'active'
                    }
                ]
            }, null, 2)
        },
        {
            method: 'GET',
            path: '/clients-create',
            description: 'Show form to create new client',
            authenticated: true,
            permission: '/clients'
        },
        {
            method: 'POST',
            path: '/clients-store',
            description: 'Create new client with logo and branding',
            authenticated: true,
            permission: '/clients',
            parameters: 'name, logo, preview_url, color_palette_id'
        },
        {
            method: 'GET',
            path: '/clients-edit/{id}',
            description: 'Get client details for editing',
            authenticated: true,
            permission: '/clients'
        },
        {
            method: 'POST',
            path: '/clients-update/{id}',
            description: 'Update client information',
            authenticated: true,
            permission: '/clients'
        },
        {
            method: 'DELETE',
            path: '/clients-delete/{id}',
            description: 'Delete client (if no associated previews)',
            authenticated: true,
            permission: '/clients'
        }
    ],
    colorPalettes: [
        {
            method: 'GET',
            path: '/color-palettes',
            description: 'List all color palette themes',
            authenticated: true,
            permission: '/color-palettes'
        },
        {
            method: 'POST',
            path: '/color-palettes/store',
            description: 'Create new color palette theme',
            authenticated: true,
            permission: '/color-palettes',
            parameters: 'name, colors, preview_image'
        },
        {
            method: 'PUT',
            path: '/color-palettes-update/{id}',
            description: 'Update color palette configuration',
            authenticated: true,
            permission: '/color-palettes'
        },
        {
            method: 'PUT',
            path: '/color-palettes-toggle-status/{id}',
            description: 'Toggle color palette active status',
            authenticated: true,
            permission: '/color-palettes'
        },
        {
            method: 'DELETE',
            path: '/color-palettes-delete/{id}',
            description: 'Delete color palette theme',
            authenticated: true,
            permission: '/color-palettes'
        }
    ],
    bills: [
        {
            method: 'GET',
            path: '/bills',
            description: 'List all bills with sub-bills',
            authenticated: true,
            permission: '/bills'
        },
        {
            method: 'GET',
            path: '/bills/{id}',
            description: 'View detailed bill with all sub-bills',
            authenticated: true,
            permission: '/bills'
        },
        {
            method: 'GET',
            path: '/bills-create',
            description: 'Show form to create new bill',
            authenticated: true,
            permission: '/bills'
        },
        {
            method: 'POST',
            path: '/bills-create-post',
            description: 'Create new bill with sub-bills',
            authenticated: true,
            permission: '/bills'
        },
        {
            method: 'GET',
            path: '/bills-edit/{id}',
            description: 'Get bill details for editing',
            authenticated: true,
            permission: '/bills'
        },
        {
            method: 'PUT',
            path: '/bills-edit/{id}',
            description: 'Update bill and sub-bills',
            authenticated: true,
            permission: '/bills'
        },
        {
            method: 'DELETE',
            path: '/bills-delete/{id}',
            description: 'Delete bill and all sub-bills',
            authenticated: true,
            permission: '/bills'
        },
        {
            method: 'GET',
            path: '/bills-download/{id}',
            description: 'Download bill as PDF',
            authenticated: true,
            permission: '/bills'
        }
    ],
    media: [
        {
            method: 'GET',
            path: '/medias',
            description: 'List all media assets in library',
            authenticated: true,
            permission: '/medias'
        },
        {
            method: 'POST',
            path: '/medias-store',
            description: 'Upload new media to library',
            authenticated: true,
            permission: '/medias',
            parameters: 'file, name, type, description'
        },
        {
            method: 'DELETE',
            path: '/medias-delete/{id}',
            description: 'Delete media from library',
            authenticated: true,
            permission: '/medias'
        },
        {
            method: 'GET',
            path: '/medias-download/{id}',
            description: 'Download media file',
            authenticated: true,
            permission: '/medias'
        }
    ],
    userManagement: [
        {
            method: 'GET',
            path: '/user-managements/designations',
            description: 'List all user designations/roles',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'POST',
            path: '/user-managements/designations-create-post',
            description: 'Create new user designation',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'PUT',
            path: '/user-managements/designations-edit/{id}',
            description: 'Update user designation',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'DELETE',
            path: '/user-managements/designations-delete/{id}',
            description: 'Delete user designation',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'GET',
            path: '/user-managements/users',
            description: 'List all users with permissions',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'POST',
            path: '/user-managements/users-create',
            description: 'Create new user account',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'DELETE',
            path: '/user-managements/users-delete/{id}',
            description: 'Delete user account',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'PUT',
            path: '/user-managements/users/update/permissions/{id}',
            description: 'Update user permissions',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'PUT',
            path: '/user-managements/users/{id}/update-role',
            description: 'Update user role/designation',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'PUT',
            path: '/user-managements/users/{user}/client',
            description: 'Assign user to specific client',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'POST',
            path: '/user-managements/users/update-password/{id}',
            description: 'Update user password',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'GET',
            path: '/user-managements/routes',
            description: 'List all route permissions',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'POST',
            path: '/user-managements/routes-create-post',
            description: 'Create new route permission',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'PUT',
            path: '/user-managements/routes-edit/{id}',
            description: 'Update route permission',
            authenticated: true,
            permission: '/user-managements'
        },
        {
            method: 'DELETE',
            path: '/user-managements/routes-delete/{id}',
            description: 'Delete route permission',
            authenticated: true,
            permission: '/user-managements'
        }
    ],
    previewApi: [
        {
            method: 'GET',
            path: '/previews/show/{slug}',
            description: 'Public preview display (client-facing)',
            authenticated: false,
            note: 'This is the main preview URL shared with clients'
        },
        {
            method: 'GET',
            path: '/preview/renderCategories/{id}',
            description: 'AJAX: Render categories for preview',
            authenticated: false,
            returnType: 'JSON'
        },
        {
            method: 'GET',
            path: '/preview/updateActiveCategory/{id}',
            description: 'AJAX: Set active category in preview',
            authenticated: false,
            returnType: 'JSON'
        },
        {
            method: 'GET',
            path: '/preview/updateActiveFeedback/{feedback_id}',
            description: 'AJAX: Set active feedback in preview',
            authenticated: false,
            returnType: 'JSON'
        },
        {
            method: 'GET',
            path: '/preview/renderVersions/{feedbackSet_id}',
            description: 'AJAX: Render versions for feedback set',
            authenticated: false,
            returnType: 'JSON'
        },
        {
            method: 'GET',
            path: '/preview/renderBanners/{version_id}',
            description: 'AJAX: Render banners for version',
            authenticated: false,
            returnType: 'JSON'
        },
        {
            method: 'GET',
            path: '/preview/renderGifs/{version_id}',
            description: 'AJAX: Render GIFs for version',
            authenticated: false,
            returnType: 'JSON'
        },
        {
            method: 'GET',
            path: '/preview/renderSocials/{version_id}',
            description: 'AJAX: Render social images for version',
            authenticated: false,
            returnType: 'JSON'
        },
        {
            method: 'GET',
            path: '/preview/renderVideos/{version_id}',
            description: 'AJAX: Render videos for version',
            authenticated: false,
            returnType: 'JSON'
        },
        {
            method: 'GET',
            path: '/preview/{preview_id}/change/theme/{color_id}',
            description: 'AJAX: Change preview theme/color palette',
            authenticated: false,
            returnType: 'JSON'
        }
    ],
    tracking: [
        {
            method: 'GET',
            path: '/get-viewers/{page_id}',
            description: 'Get current viewers for a preview page',
            authenticated: false,
            returnType: 'JSON'
        },
        {
            method: 'POST',
            path: '/track-viewer',
            description: 'Track viewer activity on preview page',
            authenticated: false,
            parameters: 'page_id, user_info'
        }
    ],
    authentication: [
        {
            method: 'POST',
            path: '/preview-login',
            description: 'Login specifically for preview access',
            authenticated: false,
            rateLimit: '5 per minute',
            parameters: 'email, password, remember'
        },
        {
            method: 'POST',
            path: '/logout-preview',
            description: 'Logout from preview and return to preview page',
            authenticated: false,
            parameters: 'preview_id'
        },
        {
            method: 'GET',
            path: '/change-password',
            description: 'Show password change form',
            authenticated: true
        },
        {
            method: 'POST',
            path: '/change-password-post',
            description: 'Update user password',
            authenticated: true,
            parameters: 'current_password, password, password_confirmation'
        }
    ],
    systemManagement: [
        {
            method: 'GET',
            path: '/activity-logs',
            description: 'View system activity logs',
            authenticated: true,
            permission: '/activity-logs'
        },
        {
            method: 'GET',
            path: '/cache-management',
            description: 'Cache management dashboard',
            authenticated: true,
            permission: '/cache-management'
        },
        {
            method: 'GET',
            path: '/api/cache-management/stats',
            description: 'Get cache statistics',
            authenticated: true,
            permission: '/cache-management',
            returnType: 'JSON'
        },
        {
            method: 'GET',
            path: '/cache-management/scheduler-status',
            description: 'Get cache cleanup scheduler status',
            authenticated: true,
            permission: '/cache-management',
            returnType: 'JSON'
        },
        {
            method: 'POST',
            path: '/cache-management/run-cleanup',
            description: 'Manually trigger cache cleanup',
            authenticated: true,
            permission: '/cache-management'
        },
        {
            method: 'GET',
            path: '/cache-management/scheduler-settings',
            description: 'Get scheduler configuration',
            authenticated: true,
            permission: '/cache-management',
            returnType: 'JSON'
        },
        {
            method: 'POST',
            path: '/cache-management/scheduler-settings',
            description: 'Update scheduler configuration',
            authenticated: true,
            permission: '/cache-management'
        },
        {
            method: 'GET',
            path: '/cache-management/server-time',
            description: 'Get current server time',
            authenticated: true,
            permission: '/cache-management',
            returnType: 'JSON'
        },
        {
            method: 'GET',
            path: '/cache-management/system-info',
            description: 'Get system information',
            authenticated: true,
            permission: '/cache-management',
            returnType: 'JSON'
        },
        {
            method: 'POST',
            path: '/cache-management/blank-logs',
            description: 'Clear all log files',
            authenticated: true,
            permission: '/cache-management'
        },
        {
            method: 'POST',
            path: '/cache-management/run-artisan-clear',
            description: 'Run artisan cache clear commands',
            authenticated: true,
            permission: '/cache-management'
        },
        {
            method: 'GET',
            path: '/logs',
            description: 'Log viewer dashboard',
            authenticated: true,
            permission: '/logs'
        },
        {
            method: 'GET',
            path: '/logs/data',
            description: 'Get log file data',
            authenticated: true,
            permission: '/logs',
            returnType: 'JSON'
        },
        {
            method: 'GET',
            path: '/logs/download',
            description: 'Download log file',
            authenticated: true,
            permission: '/logs'
        },
        {
            method: 'POST',
            path: '/logs/clear',
            description: 'Clear log file',
            authenticated: true,
            permission: '/logs'
        }
    ],
    miscellaneous: [
        {
            method: 'GET',
            path: '/play/tetris',
            description: 'Tetris game interface',
            authenticated: true,
            permission: '/play/tetris'
        },
        {
            method: 'POST',
            path: '/tetris/submit/score',
            description: 'Submit tetris game score',
            authenticated: true,
            permission: '/play/tetris',
            parameters: 'score, level, lines'
        },
        {
            method: 'GET',
            path: '/welcome-to-planetnine/register',
            description: 'New user registration form',
            authenticated: false
        },
        {
            method: 'POST',
            path: '/welcome-to-planetnine/register-post',
            description: 'Process new user registration',
            authenticated: false,
            parameters: 'name, email, password, designation_id'
        }
    ]
})

const responseCodes = ref([
    {
        code: '200',
        title: 'Success',
        description: 'Request completed successfully',
        badgeClass: 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200'
    },
    {
        code: '201',
        title: 'Created',
        description: 'Resource created successfully',
        badgeClass: 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200'
    },
    {
        code: '400',
        title: 'Bad Request',
        description: 'Invalid request parameters',
        badgeClass: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200'
    },
    {
        code: '401',
        title: 'Unauthorized',
        description: 'Authentication required',
        badgeClass: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200'
    },
    {
        code: '403',
        title: 'Forbidden',
        description: 'Insufficient permissions',
        badgeClass: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200'
    },
    {
        code: '429',
        title: 'Too Many Requests',
        description: 'Rate limit exceeded',
        badgeClass: 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-200'
    }
])

const getMethodBadgeClass = (method: string) => {
    const classes = {
        GET: 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200',
        POST: 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200',
        PUT: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200',
        DELETE: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200'
    }
    return classes[method as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

// Get category icon
const getCategoryIcon = (category: string) => {
    const icons: Record<string, string> = {
        'dashboard': '📊',
        'authentication': '🔑',
        'previews': '👁️',
        'fileTransfers': '📁',
        'userManagement': '👥',
        'clientManagement': '🏢',
        'systemAdmin': '⚙️',
        'clientApis': '🌐',
        'tracking': '📊',
        'utilities': '🛠️'
    };
    return icons[category] || '📋';
};

// Format category name
const formatCategoryName = (category: string) => {
    const names: Record<string, string> = {
        'dashboard': 'Dashboard & Core',
        'authentication': 'Authentication',
        'previews': 'Preview Management',
        'fileTransfers': 'File Transfers',
        'userManagement': 'User Management',
        'clientManagement': 'Client Management',
        'systemAdmin': 'System Administration',
        'clientApis': 'Public Client APIs',
        'tracking': 'Analytics & Tracking',
        'utilities': 'Utilities & Tools'
    };
    return names[category] || category.charAt(0).toUpperCase() + category.slice(1);
};


const qaList = ref([
    {
        question: 'What is Creative Studio Planet Nine?',
        answer: 'Creative Studio Planet Nine is a platform designed to generate preview links for clients. It allows uploading Banners, Videos, GIFs, and Social Images (collectively called Creatives). This system is called the Preview System. To manage it, there is a hierarchy of users, models, and permissions.',
        additionalInfo: `
        The system offers multiple features. Please refer to the left sidebar while reading the points:
        <ul class="list-disc list-inside space-y-3 mt-4">
            <li><span class="font-bold text-blue-600">Color Palettes:</span> Manage colors/themes for clients. Supports adding/editing colors and uploading preview images.</li>
            <li><span class="font-bold text-green-600">Client Handling:</span> Each client has a unique color theme (derived from Color Palettes), logo, and preview URL.</li>
            <li><span class="font-bold text-purple-600">Banner and Video Sizes:</span> Creatives (Banners, Videos, GIFs, Socials) require predefined sizes. These sizes are maintained to display previews dynamically.</li>
            <li><span class="font-bold text-orange-600">File Transfers:</span> Generate secure transfer links so clients can download their creatives. Transfers are to be deleted after a specific period.</li>
            <li><span class="font-bold text-red-600">Bills:</span> Module for managing office bills.</li>
            <li><span class="font-bold text-indigo-600">Media Library:</span> Central place for uploading and referencing assets (images, videos, external links).</li>
            <li><span class="font-bold text-pink-600">Preview System:</span> The core feature. Enables creation of preview links and uploading creatives. Evolved over time, but core remains the same.</li>
        </ul>

        <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg border-l-4 border-green-400 shadow">
            <h5 class="font-bold text-green-800 dark:text-green-300 mb-2">Key Points</h5>
            <ul class="list-disc list-inside space-y-1 text-sm text-green-700 dark:text-green-300">
                <li>Backend: Laravel 12</li>
                <li>Frontend: Vue.js 3</li>
                <li>Scaffolding: Vite.js</li>
                <li>SPA Rendering: Inertia.js</li>
                <li>Creative Assets stored in: <code>public/uploads</code></li>
                <li>File Transfers stored in: <code>public/Transfer Files</code></li>
            </ul>
        </div>
    `
    },
    {
        question: 'What is a Client?',
        answer: 'Clients are the organizations for whom creatives are developed/designed. Each client has its own logo, brand colors, URL, and optionally a dedicated preview URL (e.g., creative.cmn.com or creative.merkle.com).',
        additionalInfo: `
        Technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code>Client.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code>2025_05_02_135230_create_clients_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code>ClientController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code>Pages/Clients</code></li>
        </ul>
    `
    },
    {
        question: 'What are the Color Palettes?',
        answer: 'Color Palettes define themes for the preview system. A theme can include multiple colors and improves the overall interface design. Each Color Palette has a name and color set.',
        additionalInfo: `
        Technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code>ColorPalette.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code>2025_05_01_082516_create_color_palettes_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code>ColorPaletteController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code>Pages/ColorPalettes</code></li>
        </ul>
    `
    },
    {
        question: 'What are Banner Sizes and Video Sizes?',
        answer: 'These define the dimensions of creatives. Banner Sizes are for banners only; Video Sizes are for videos only. Each has a name, width, and height.',
        additionalInfo: `
        Technical details:
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2">Banner Sizes</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>BannerSize.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_04_15_070726_create_banner_sizes_table.php</code></li>
                    <li><strong>Controller:</strong> <code>BannerSizeController.php</code></li>
                    <li><strong>Vue Files:</strong> <code>Pages/BannerSizes</code></li>
                </ul>
            </div>
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-green-600 mb-2">Video Sizes</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>VideoSize.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_04_17_173143_create_video_sizes_table.php</code></li>
                    <li><strong>Controller:</strong> <code>VideoSizeController.php</code></li>
                    <li><strong>Vue Files:</strong> <code>Pages/VideoSizes</code></li>
                </ul>
            </div>
        </div>
    `
    },
    {
        question: 'What are File Transfers?',
        answer: 'File Transfers allow clients to download creatives via secure links. Each transfer has a unique URL and is must be manually deleted after 1 year and 1 month to save space on the server. Files are to be stored on the <span class="font-bold">DIVANAS</span> server. The File Transfer link can be generated two ways. Either going to the Index page of File Transfer and create a new row or while working on a Preview, if a Feedback gets approved then on Approve click the system will ask for the zip files to upload.',
        additionalInfo: `
        Technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code>FileTransfer.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code>2025_05_01_134500_create_file_transfers_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code>FileTransferController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code>Pages/FileTransfers</code></li>
        </ul>
    `
    },
    {
        question: 'What is Media Library?',
        answer: 'The Media Library is a central storage for assets used in previews. Each file has a name, type (image, video, or other), and URL. This segment is needed when Video banners to be developed but sometimes Google`s internal system cannot handle the video file within the banner. So, because of that we had to make another way to store videos separately and call them while needed within the banners.',
        additionalInfo: `
        Technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code>Media.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code>2025_05_03_114912_create_media_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code>MediaController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code>Pages/Medias</code></li>
        </ul>
    `
    },
    {
        question: 'What is Bills?',
        answer: 'Bills module helps manage office expenses. Each Bill can contain multiple Sub Bills, and totals are auto-calculated. This section will be handled by Limon Roy majority of the time.',
        additionalInfo: `
        Technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Models:</strong> <code>Bill.php</code>, <code>SubBill.php</code></li>
            <li>🗃️ <strong>Migrations:</strong> <code>2025_04_21_123650_create_bills_table.php</code>, <code>2025_04_21_123714_create_sub_bills_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code>BillController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code>Pages/Bills</code></li>
        </ul>
    `
    },
    {
        question: 'How does the Preview System work and what are the relations?',
        answer: 'The Preview System is the core of Creative Studio Planet Nine. It allows creating preview links, uploading creatives, and managing feedbacks. It follows a hierarchical relationship between entities.',
        additionalInfo: `
        <h5 class="font-bold mb-2">System Hierarchy</h5>
        <ol class="list-decimal list-inside space-y-2">
            <li>Each <strong>Preview</strong> is linked to a <strong>Client</strong>, <strong>Color Palette</strong>, and <strong>User</strong>.</li>
            <li>Each Preview contains multiple <strong>Categories</strong> (Banner, Video, Gif, Social).</li>
            <li>Each Category can have multiple <strong>Feedbacks</strong>.</li>
            <li>Each Feedback can contain multiple <strong>Feedback Sets</strong>.</li>
            <li>Each Feedback Set can contain multiple <strong>Versions</strong>.</li>
            <li>Each Version can contain multiple <strong>Creatives</strong> (Banner, Video, Gif, Social Image).</li>
        </ol>

        <div class="mt-6 p-4 bg-green-50 rounded-lg border-l-4 border-green-400 shadow">
            <h5 class="font-bold mb-2">Key Features</h5>
            <ul class="list-disc list-inside text-sm">
                <li>Dynamic previews adapt to creative type.</li>
                <li>Feedback system supports multiple iterations.</li>
                <li>Color themes can be changed per Preview.</li>
            </ul>
        </div>
    `
    },
    {
        question: 'What are the files related to Preview System?',
        answer: 'The Preview System uses multiple interconnected models, controllers, and migrations. The main Preview has its own Vue page, while related entities are handled inside Update.vue. For better understanding I have serialized each component below.',
        additionalInfo: `
        There are total 6 <span class="font-bold">LAYERS:</span>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <!-- Previews -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">1</span> Preview</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newPreview.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123503_create_new_previews_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newPreviewController.php</code></li>
                    <li><strong>Vue Files:</strong> <code>Pages/Previews</code></li>
                </ul>
            </div>
            <!-- Categories -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">2</span> Categories</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newCategory.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123522_create_new_categories_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newCategoryController.php</code></li>
                </ul>
            </div>
            <!-- Feedback -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">3</span> Feedback</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newFeedback.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123537_create_new_feedback_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newFeedbackController.php</code></li>
                </ul>
            </div>
            <!-- Feedback Set -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">4</span> Feedback Set</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newFeedbackSet.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123556_create_new_feedback_sets_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newFeedbackSetController.php</code></li>
                </ul>
            </div>
            <!-- Versions -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">5</span> Versions</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newVersion.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123611_create_new_versions_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newVersionController.php</code></li>
                </ul>
            </div>
            <!-- Banners -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">6A</span> Banners</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newBanner.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123653_create_new_banners_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newBannerController.php</code></li>
                </ul>
            </div>
            <!-- Videos -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">6B</span> Videos</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newVideo.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_15_120554_create_new_videos_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newVideoController.php</code></li>
                </ul>
            </div>
            <!-- Gifs -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">6C</span> Gifs</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newGif.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_15_120605_create_new_gifs_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newGifController.php</code></li>
                </ul>
            </div>
            <!-- Socials -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">6D</span> Socials</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newSocial.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_15_120601_create_new_socials_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newSocialController.php</code></li>
                </ul>
            </div>
        </div>
        <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg border-l-4 border-green-400 shadow">
            <h5 class="font-bold text-green-800 dark:text-green-300 mb-2">Key Points</h5>
            <ul class="list-disc list-inside space-y-1 text-sm text-green-700 dark:text-green-300">
                <li>Only Previews has a dedicated Vue view file.</li>
                <li>Other entities are managed within <code>Pages/Previews/Update.vue</code>.</li>
                <li>The Update.vue file allows creating, editing, and deleting all related entities.</li>
                <li>The codebase is complex; consider using AI/code analysis tools for better understanding.</li>
                <li>The reason there are 6 Layers is to show the client a robust preview to differentiate their contents and requirements.</li>
            </ul>
        </div>
    `
    }

])

const filteredQA = computed(() => {
    if (!search.value) return qaList.value
    const s = search.value.toLowerCase()
    return qaList.value.filter(
        qa => qa.question.toLowerCase().includes(s) || qa.answer.toLowerCase().includes(s) || qa.additionalInfo?.toLowerCase().includes(s)
    )
})

const filteredApiEndpoints = computed(() => {
    if (!apiSearch.value) return apiEndpoints.value

    const s = apiSearch.value.toLowerCase()
    const filtered: typeof apiEndpoints.value = {}

    // Search through each category and its endpoints
    Object.entries(apiEndpoints.value).forEach(([category, endpoints]) => {
        const filteredEndpoints = endpoints.filter(endpoint => {
            return (
                // Search in category name
                category.toLowerCase().includes(s) ||
                // Search in method
                endpoint.method.toLowerCase().includes(s) ||
                // Search in path
                endpoint.path.toLowerCase().includes(s) ||
                // Search in description
                endpoint.description.toLowerCase().includes(s) ||
                // Search in permission if exists
                ((endpoint as any).permission && (endpoint as any).permission.toLowerCase().includes(s)) ||
                // Search in parameters if exists
                ((endpoint as any).parameters && (endpoint as any).parameters.toLowerCase().includes(s))
            )
        })

        // Only include category if it has matching endpoints
        if (filteredEndpoints.length > 0) {
            filtered[category] = filteredEndpoints
        }
    })

    return filtered
})

function toggle(idx: number) {
    openIdx.value = openIdx.value === idx ? null : idx
}
</script>

<style scoped>
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s ease-in;
}

.slide-fade-enter-from {
    transform: translateY(-10px);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateY(-5px);
    opacity: 0;
}

/* Custom styles for lists */
:deep(ul) {
    list-style-type: disc;
    padding-left: .5rem;
}

:deep(li) {
    margin: 0.5rem 0;
}

:deep(code) {
    background-color: rgba(156, 163, 175, 0.2);
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    word-break: break-word;
    overflow-wrap: break-word;
}

:deep(.dark code) {
    background-color: rgba(75, 85, 99, 0.5);
}

/* Mobile responsive fixes */
@media (max-width: 640px) {
    :deep(pre) {
        font-size: 0.75rem;
        line-height: 1.25;
    }

    :deep(code) {
        font-size: 0.75rem;
    }

    /* Ensure horizontal scrolling works on mobile */
    :deep(pre code) {
        white-space: pre;
        word-break: normal;
        overflow-wrap: normal;
    }
}
</style>