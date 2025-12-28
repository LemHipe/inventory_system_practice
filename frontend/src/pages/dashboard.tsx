import { useQuery } from '@tanstack/react-query';
import { Package, Warehouse, AlertTriangle } from 'lucide-react';
import api from '@/lib/api';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

interface DashboardData {
    success: boolean;
    data: {
        total_items: number;
        total_warehouses: number;
        low_stock_count: number;
    };
}

export function DashboardPage() {
    const { data, isLoading } = useQuery<DashboardData>({
        queryKey: ['dashboard'],
        queryFn: async () => {
            const response = await api.get('/dashboard');
            return response.data;
        },
    });

    const stats = data?.data;

    return (
        <div className="space-y-8">
            <div>
                <h2 className="text-3xl font-bold tracking-tight">Dashboard</h2>
                <p className="text-muted-foreground">Overview of your inventory system</p>
            </div>

            {isLoading ? (
                <div className="grid gap-6 md:grid-cols-3">
                    {[...Array(3)].map((_, i) => (
                        <Card key={i} className="relative overflow-hidden">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <div className="h-4 w-24 animate-pulse rounded bg-muted" />
                            </CardHeader>
                            <CardContent>
                                <div className="h-10 w-20 animate-pulse rounded bg-muted" />
                            </CardContent>
                        </Card>
                    ))}
                </div>
            ) : (
                <div className="grid gap-6 md:grid-cols-3">
                    {/* Total Items Card */}
                    <Card className="relative overflow-hidden border-l-4 border-l-blue-500 bg-gradient-to-br from-blue-50 to-white dark:from-blue-950/20 dark:to-background">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Total Items</CardTitle>
                            <div className="rounded-full bg-blue-100 p-2 dark:bg-blue-900/30">
                                <Package className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-4xl font-bold text-blue-700 dark:text-blue-400">
                                {stats?.total_items ?? 0}
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">Items in inventory</p>
                        </CardContent>
                    </Card>

                    {/* Total Warehouses Card */}
                    <Card className="relative overflow-hidden border-l-4 border-l-emerald-500 bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-950/20 dark:to-background">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Total Warehouses</CardTitle>
                            <div className="rounded-full bg-emerald-100 p-2 dark:bg-emerald-900/30">
                                <Warehouse className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-4xl font-bold text-emerald-700 dark:text-emerald-400">
                                {stats?.total_warehouses ?? 0}
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">Active warehouses</p>
                        </CardContent>
                    </Card>

                    {/* Low Stock Card */}
                    <Card className={cn(
                        "relative overflow-hidden border-l-4 transition-colors",
                        (stats?.low_stock_count ?? 0) > 0
                            ? "border-l-red-500 bg-gradient-to-br from-red-50 to-white dark:from-red-950/20 dark:to-background"
                            : "border-l-green-500 bg-gradient-to-br from-green-50 to-white dark:from-green-950/20 dark:to-background"
                    )}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Low Stock</CardTitle>
                            <div className={cn(
                                "rounded-full p-2",
                                (stats?.low_stock_count ?? 0) > 0
                                    ? "bg-red-100 dark:bg-red-900/30"
                                    : "bg-green-100 dark:bg-green-900/30"
                            )}>
                                <AlertTriangle className={cn(
                                    "h-5 w-5",
                                    (stats?.low_stock_count ?? 0) > 0
                                        ? "text-red-600 dark:text-red-400"
                                        : "text-green-600 dark:text-green-400"
                                )} />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className={cn(
                                "text-4xl font-bold",
                                (stats?.low_stock_count ?? 0) > 0
                                    ? "text-red-700 dark:text-red-400"
                                    : "text-green-700 dark:text-green-400"
                            )}>
                                {stats?.low_stock_count ?? 0}
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {(stats?.low_stock_count ?? 0) > 0
                                    ? "Items with 20 or fewer in stock"
                                    : "All items are well-stocked"
                                }
                            </p>
                        </CardContent>
                    </Card>
                </div>
            )}

            {/* Welcome message */}
            <Card className="bg-gradient-to-r from-primary/5 via-primary/10 to-primary/5">
                <CardContent className="pt-6">
                    <div className="flex items-center gap-4">
                        <div className="rounded-full bg-primary/10 p-3">
                            <Package className="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold">Welcome to Inventory Management System</h3>
                            <p className="text-sm text-muted-foreground">
                                Monitor your inventory, manage warehouses, and track dispatches efficiently.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
