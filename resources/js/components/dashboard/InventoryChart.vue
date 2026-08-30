<script setup>
import { computed } from "vue";
import {
    ArcElement,
    Chart as ChartJS,
    Legend,
    Tooltip,
} from "chart.js";
import { Doughnut } from "vue-chartjs";

ChartJS.register(
    ArcElement,
    Tooltip,
    Legend,
);

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_products: 0,
            in_stock: 0,
            out_of_stock: 0,
        }),
    },
});

const totalProducts = computed(() =>
    Number(props.stats?.total_products) || 0,
);

const inStock = computed(() =>
    Number(props.stats?.in_stock) || 0,
);

const outOfStock = computed(() =>
    Number(props.stats?.out_of_stock) || 0,
);

const lowStock = computed(() =>
    Math.max(
        totalProducts.value -
            inStock.value -
            outOfStock.value,
        0,
    ),
);

const hasData = computed(() =>
    totalProducts.value > 0,
);

const chartData = computed(() => ({
    labels: [
        "In Stock",
        "Low Stock",
        "Out of Stock",
    ],

    datasets: [
        {
            data: [
                inStock.value,
                lowStock.value,
                outOfStock.value,
            ],

            backgroundColor: [
                "#10b981",
                "#f59e0b",
                "#ef4444",
            ],

            borderWidth: 0,
            hoverOffset: 6,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,

    cutout: "72%",

    plugins: {
        legend: {
            display: false,
        },

        tooltip: {
            padding: 12,

            callbacks: {
                label(context) {
                    return ` ${context.label}: ${context.raw}`;
                },
            },
        },
    },
};
</script>

<template>
    <div
        class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
    >
        <!-- HEADER -->

        <div
            class="border-b border-gray-100 px-5 py-5 dark:border-gray-800 sm:px-6"
        >
            <div
                class="flex items-center justify-between"
            >
                <div>
                    <h2
                        class="text-base font-semibold text-gray-900 dark:text-white"
                    >
                        Inventory Overview
                    </h2>

                    <p
                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                        Current product stock status
                    </p>
                </div>

                <div
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800"
                >
                    <svg
                        class="h-5 w-5 text-gray-600 dark:text-gray-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M4 19V5m0 14h16M8 16v-4m4 4V8m4 8V5m4 11V9"
                        />
                    </svg>
                </div>
            </div>
        </div>

        <!-- CHART -->

        <div
            v-if="hasData"
            class="px-5 py-6 sm:px-6"
        >
            <div
                class="mx-auto h-64 max-w-xs"
            >
                <Doughnut
                    :data="chartData"
                    :options="chartOptions"
                />
            </div>

            <!-- LEGEND -->

            <div
                class="mt-6 grid grid-cols-3 gap-3"
            >
                <!-- IN STOCK -->

                <div class="text-center">
                    <div
                        class="flex items-center justify-center gap-1.5"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-emerald-500"
                        ></span>

                        <span
                            class="text-xs font-medium text-gray-500 dark:text-gray-400"
                        >
                            In Stock
                        </span>
                    </div>

                    <p
                        class="mt-1 text-lg font-bold text-gray-900 dark:text-white"
                    >
                        {{ inStock }}
                    </p>
                </div>

                <!-- LOW STOCK -->

                <div class="text-center">
                    <div
                        class="flex items-center justify-center gap-1.5"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-amber-500"
                        ></span>

                        <span
                            class="text-xs font-medium text-gray-500 dark:text-gray-400"
                        >
                            Low Stock
                        </span>
                    </div>

                    <p
                        class="mt-1 text-lg font-bold text-gray-900 dark:text-white"
                    >
                        {{ lowStock }}
                    </p>
                </div>

                <!-- OUT OF STOCK -->

                <div class="text-center">
                    <div
                        class="flex items-center justify-center gap-1.5"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-red-500"
                        ></span>

                        <span
                            class="text-xs font-medium text-gray-500 dark:text-gray-400"
                        >
                            Out
                        </span>
                    </div>

                    <p
                        class="mt-1 text-lg font-bold text-gray-900 dark:text-white"
                    >
                        {{ outOfStock }}
                    </p>
                </div>
            </div>
        </div>

        <!-- EMPTY STATE -->

        <div
            v-else
            class="flex min-h-[330px] flex-col items-center justify-center px-6 text-center"
        >
            <div
                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800"
            >
                <svg
                    class="h-7 w-7 text-gray-400 dark:text-gray-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10l-8 4"
                    />
                </svg>
            </div>

            <p
                class="mt-4 text-sm font-semibold text-gray-900 dark:text-white"
            >
                No inventory data
            </p>

            <p
                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
            >
                Add products to see your inventory overview.
            </p>
        </div>
    </div>
</template>
