import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Truck, CheckCircle, Clock, XCircle } from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';

interface Dispatch {
  id: string;
  transaction_code: string;
  inventory_id: string;
  warehouse_id: string;
  dispatcher_id: number;
  quantity: number;
  destination: string;
  notes: string | null;
  status: 'pending' | 'in_transit' | 'delivered' | 'cancelled';
  dispatched_at: string;
  delivered_at: string | null;
  inventory: { id: string; product_name: string; item_code?: string } | null;
  warehouse: { id: string; name: string } | null;
  dispatcher: { id: number; name: string } | null;
}

interface DispatchResponse {
  success: boolean;
  data: Dispatch[];
}

interface Warehouse {
  id: string;
  name: string;
}

interface InventoryItem {
  id: string;
  product_name: string;
  quantity: number;
}

const statusConfig = {
  pending: { label: 'Pending', icon: Clock, className: 'text-yellow-600 bg-yellow-100' },
  in_transit: { label: 'In Transit', icon: Truck, className: 'text-blue-600 bg-blue-100' },
  delivered: { label: 'Delivered', icon: CheckCircle, className: 'text-green-600 bg-green-100' },
  cancelled: { label: 'Cancelled', icon: XCircle, className: 'text-red-600 bg-red-100' },
};

export function DispatchesPage() {
  const [isOpen, setIsOpen] = useState(false);
  const [formData, setFormData] = useState({
    inventory_id: '',
    warehouse_id: '',
    quantity: '',
    destination: 'Bosun Hardware',
    notes: '',
  });

  const queryClient = useQueryClient();

  const { data: dispatches, isLoading } = useQuery<DispatchResponse>({
    queryKey: ['dispatches'],
    queryFn: async () => {
      const response = await api.get('/dispatches');
      return response.data;
    },
  });

  const { data: warehouses } = useQuery<{ success: boolean; data: Warehouse[] }>({
    queryKey: ['warehouses'],
    queryFn: async () => {
      const response = await api.get('/warehouses');
      return response.data;
    },
  });

  const { data: inventory } = useQuery<{ success: boolean; data: InventoryItem[] }>({
    queryKey: ['inventory'],
    queryFn: async () => {
      const response = await api.get('/inventory');
      return response.data;
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof formData) => {
      const response = await api.post('/dispatches', {
        ...data,
        quantity: parseInt(data.quantity),
      });
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['dispatches'] });
      queryClient.invalidateQueries({ queryKey: ['inventory'] });
      queryClient.invalidateQueries({ queryKey: ['dashboard'] });
      toast.success('Dispatch created successfully');
      setIsOpen(false);
      setFormData({ inventory_id: '', warehouse_id: '', quantity: '', destination: 'Bosun Hardware', notes: '' });
    },
    onError: () => {
      toast.error('Failed to create dispatch');
    },
  });

  const updateStatusMutation = useMutation({
    mutationFn: async ({ id, status }: { id: string; status: string }) => {
      const response = await api.put(`/dispatches/${id}`, { status });
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['dispatches'] });
      toast.success('Status updated');
    },
    onError: () => {
      toast.error('Failed to update status');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate(formData);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Dispatches</h2>
          <p className="text-muted-foreground">Track and manage item dispatches</p>
        </div>
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              New Dispatch
            </Button>
          </DialogTrigger>
          <DialogContent>
            <form onSubmit={handleSubmit}>
              <DialogHeader>
                <DialogTitle>Create New Dispatch</DialogTitle>
                <DialogDescription>Dispatch items from a warehouse</DialogDescription>
              </DialogHeader>
              <div className="grid gap-4 py-4">
                <div className="grid gap-2">
                  <Label htmlFor="warehouse_id">From Warehouse</Label>
                  <select
                    id="warehouse_id"
                    value={formData.warehouse_id}
                    onChange={(e) => setFormData({ ...formData, warehouse_id: e.target.value })}
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    required
                  >
                    <option value="">Select warehouse...</option>
                    {warehouses?.data?.map((w) => (
                      <option key={w.id} value={w.id}>{w.name}</option>
                    ))}
                  </select>
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="inventory_id">Item</Label>
                  <select
                    id="inventory_id"
                    value={formData.inventory_id}
                    onChange={(e) => setFormData({ ...formData, inventory_id: e.target.value })}
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    required
                  >
                    <option value="">Select item...</option>
                    {inventory?.data?.map((item) => (
                      <option key={item.id} value={item.id}>
                        {item.product_name} (Stock: {item.quantity})
                      </option>
                    ))}
                  </select>
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="quantity">Quantity</Label>
                  <Input
                    id="quantity"
                    type="number"
                    min="1"
                    value={formData.quantity}
                    onChange={(e) => setFormData({ ...formData, quantity: e.target.value })}
                    required
                  />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="destination">Destination</Label>
                  <Input
                    id="destination"
                    value={formData.destination}
                    onChange={(e) => setFormData({ ...formData, destination: e.target.value })}
                    placeholder="Bosun Hardware"
                    required
                  />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="notes">Notes (Optional)</Label>
                  <Input
                    id="notes"
                    value={formData.notes}
                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                    placeholder="Any additional notes..."
                  />
                </div>
              </div>
              <DialogFooter>
                <Button type="submit" disabled={createMutation.isPending}>
                  {createMutation.isPending ? 'Creating...' : 'Create Dispatch'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Transaction Code</TableHead>
              <TableHead>Item</TableHead>
              <TableHead>From Warehouse</TableHead>
              <TableHead>Destination</TableHead>
              <TableHead>Dispatcher</TableHead>
              <TableHead className="text-right">Qty</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell colSpan={8} className="text-center">Loading...</TableCell>
              </TableRow>
            ) : dispatches?.data?.length === 0 ? (
              <TableRow>
                <TableCell colSpan={8} className="text-center text-muted-foreground">
                  No dispatches found
                </TableCell>
              </TableRow>
            ) : (
              dispatches?.data?.map((dispatch) => {
                const status = statusConfig[dispatch.status];
                const StatusIcon = status.icon;
                return (
                  <TableRow key={dispatch.id}>
                    <TableCell>
                      <code className="rounded bg-muted px-2 py-1 text-xs font-mono">
                        {dispatch.transaction_code || '-'}
                      </code>
                    </TableCell>
                    <TableCell className="font-medium">
                      <div>{dispatch.inventory?.product_name || 'Unknown'}</div>
                      {dispatch.inventory?.item_code && (
                        <div className="text-xs text-muted-foreground">{dispatch.inventory.item_code}</div>
                      )}
                    </TableCell>
                    <TableCell>{dispatch.warehouse?.name || 'Unknown'}</TableCell>
                    <TableCell>{dispatch.destination}</TableCell>
                    <TableCell>{dispatch.dispatcher?.name || 'Unknown'}</TableCell>
                    <TableCell className="text-right">{dispatch.quantity}</TableCell>
                    <TableCell>
                      <span className={cn('inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium', status.className)}>
                        <StatusIcon className="h-3 w-3" />
                        {status.label}
                      </span>
                    </TableCell>
                    <TableCell>
                      {dispatch.status === 'pending' && (
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => updateStatusMutation.mutate({ id: dispatch.id, status: 'in_transit' })}
                        >
                          Ship
                        </Button>
                      )}
                      {dispatch.status === 'in_transit' && (
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => updateStatusMutation.mutate({ id: dispatch.id, status: 'delivered' })}
                        >
                          Mark Delivered
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </div>
    </div>
  );
}
