import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { FileText, Package, Clock, Filter } from 'lucide-react';
import api from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

interface ActivityLog {
  id: string;
  user_id: number;
  action: string;
  model_type: string;
  model_id: string | null;
  description: string;
  old_values: Record<string, unknown> | null;
  new_values: Record<string, unknown> | null;
  ip_address: string | null;
  created_at: string;
  user: {
    id: number;
    name: string;
    email: string;
  } | null;
}

interface LogsResponse {
  success: boolean;
  data: ActivityLog[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

const actionConfig: Record<string, { label: string; className: string }> = {
  created: { label: 'Created', className: 'bg-green-100 text-green-700' },
  updated: { label: 'Updated', className: 'bg-blue-100 text-blue-700' },
  deleted: { label: 'Deleted', className: 'bg-red-100 text-red-700' },
  stock_added: { label: 'Stock Added', className: 'bg-emerald-100 text-emerald-700' },
  stock_removed: { label: 'Stock Removed', className: 'bg-amber-100 text-amber-700' },
};

export function InventoryLogsPage() {
  const [filters, setFilters] = useState({
    action: '',
    from: '',
    to: '',
  });
  const [showFilters, setShowFilters] = useState(false);

  const { data, isLoading, error } = useQuery<LogsResponse>({
    queryKey: ['activity-logs', 'Inventory', filters],
    queryFn: async () => {
      const params = new URLSearchParams();
      params.append('model_type', 'Inventory');
      if (filters.action) params.append('action', filters.action);
      if (filters.from) params.append('from', filters.from);
      if (filters.to) params.append('to', filters.to);

      const response = await api.get(`/activity-logs?${params.toString()}`);
      return response.data;
    },
  });

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-PH', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  if (error) {
    return (
      <div className="flex items-center justify-center h-64">
        <Card className="w-full max-w-md">
          <CardContent className="pt-6">
            <p className="text-center text-destructive">Access denied. Admin privileges required.</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Inventory Logs</h2>
          <p className="text-muted-foreground">All inventory movement and changes</p>
        </div>
        <Button variant="outline" onClick={() => setShowFilters(!showFilters)}>
          <Filter className="mr-2 h-4 w-4" />
          Filters
        </Button>
      </div>

      {showFilters && (
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Filter Logs</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 md:grid-cols-3">
              <div className="grid gap-2">
                <Label htmlFor="action">Action</Label>
                <select
                  id="action"
                  value={filters.action}
                  onChange={(e) => setFilters({ ...filters, action: e.target.value })}
                  className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                  <option value="">All Actions</option>
                  <option value="created">Created</option>
                  <option value="updated">Updated</option>
                  <option value="deleted">Deleted</option>
                  <option value="stock_added">Stock Added</option>
                  <option value="stock_removed">Stock Removed</option>
                </select>
              </div>
              <div className="grid gap-2">
                <Label htmlFor="from">From Date</Label>
                <Input id="from" type="date" value={filters.from} onChange={(e) => setFilters({ ...filters, from: e.target.value })} />
              </div>
              <div className="grid gap-2">
                <Label htmlFor="to">To Date</Label>
                <Input id="to" type="date" value={filters.to} onChange={(e) => setFilters({ ...filters, to: e.target.value })} />
              </div>
            </div>
            <div className="mt-4 flex gap-2">
              <Button variant="outline" onClick={() => setFilters({ action: '', from: '', to: '' })}>
                Clear Filters
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-[180px]">Date & Time</TableHead>
                <TableHead>User</TableHead>
                <TableHead>Action</TableHead>
                <TableHead>Description</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {isLoading ? (
                <TableRow>
                  <TableCell colSpan={4} className="text-center py-8">
                    <div className="flex items-center justify-center gap-2">
                      <Clock className="h-4 w-4 animate-spin" />
                      Loading logs...
                    </div>
                  </TableCell>
                </TableRow>
              ) : data?.data?.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={4} className="text-center py-8 text-muted-foreground">
                    <Package className="h-8 w-8 mx-auto mb-2 opacity-50" />
                    No inventory logs found
                  </TableCell>
                </TableRow>
              ) : (
                data?.data?.map((log) => {
                  const config = actionConfig[log.action] || { label: log.action, className: 'bg-gray-100 text-gray-700' };

                  return (
                    <TableRow key={log.id}>
                      <TableCell className="text-sm text-muted-foreground">{formatDate(log.created_at)}</TableCell>
                      <TableCell>
                        <div className="font-medium">{log.user?.name || 'Unknown'}</div>
                        <div className="text-xs text-muted-foreground">{log.user?.email}</div>
                      </TableCell>
                      <TableCell>
                        <span className={cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', config.className)}>
                          {config.label}
                        </span>
                      </TableCell>
                      <TableCell className="max-w-md">
                        <p className="truncate" title={log.description}>{log.description}</p>
                      </TableCell>
                    </TableRow>
                  );
                })
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {data?.meta && data.meta.total > 0 && (
        <div className="text-sm text-muted-foreground text-center">Showing {data.data.length} of {data.meta.total} logs</div>
      )}
    </div>
  );
}
