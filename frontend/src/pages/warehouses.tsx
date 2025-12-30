import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Trash2, Warehouse as WarehouseIcon, MapPin, Pencil } from 'lucide-react';
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
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Warehouse {
  id: string;
  name: string;
  address: string;
  city: string | null;
  state: string | null;
  postal_code: string | null;
  phone: string | null;
  is_active: boolean;
  created_at: string;
}

interface WarehouseResponse {
  success: boolean;
  data: Warehouse[];
}

export function WarehousesPage() {
  const [isOpen, setIsOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [editingWarehouse, setEditingWarehouse] = useState<Warehouse | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    phone: '',
  });
  const [editFormData, setEditFormData] = useState({
    name: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    phone: '',
  });

  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery<WarehouseResponse>({
    queryKey: ['warehouses'],
    queryFn: async () => {
      const response = await api.get('/warehouses');
      return response.data;
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof formData) => {
      const response = await api.post('/warehouses', data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['warehouses'] });
      toast.success('Warehouse created successfully');
      setIsOpen(false);
      setFormData({ name: '', address: '', city: '', state: '', postal_code: '', phone: '' });
    },
    onError: () => {
      toast.error('Failed to create warehouse');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: string) => {
      await api.delete(`/warehouses/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['warehouses'] });
      toast.success('Warehouse deleted successfully');
    },
    onError: () => {
      toast.error('Failed to delete warehouse');
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, data }: { id: string; data: typeof editFormData }) => {
      const response = await api.put(`/warehouses/${id}`, data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['warehouses'] });
      toast.success('Warehouse updated successfully');
      setEditOpen(false);
      setEditingWarehouse(null);
    },
    onError: () => {
      toast.error('Failed to update warehouse');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate(formData);
  };

  const handleEditClick = (warehouse: Warehouse) => {
    setEditingWarehouse(warehouse);
    setEditFormData({
      name: warehouse.name,
      address: warehouse.address || '',
      city: warehouse.city || '',
      state: warehouse.state || '',
      postal_code: warehouse.postal_code || '',
      phone: warehouse.phone || '',
    });
    setEditOpen(true);
  };

  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingWarehouse) return;
    updateMutation.mutate({ id: editingWarehouse.id, data: editFormData });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Warehouses</h2>
          <p className="text-muted-foreground">Manage your warehouse locations</p>
        </div>
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              Add Warehouse
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-md">
            <form onSubmit={handleSubmit}>
              <DialogHeader>
                <DialogTitle>Add New Warehouse</DialogTitle>
                <DialogDescription>Add a new warehouse location</DialogDescription>
              </DialogHeader>
              <div className="grid gap-4 py-4">
                <div className="grid gap-2">
                  <Label htmlFor="name">Warehouse Name</Label>
                  <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    placeholder="Main Warehouse"
                    required
                  />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="address">Address</Label>
                  <Input
                    id="address"
                    value={formData.address}
                    onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                    placeholder="123 Storage St"
                    required
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="grid gap-2">
                    <Label htmlFor="city">City</Label>
                    <Input
                      id="city"
                      value={formData.city}
                      onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                      placeholder="Manila"
                    />
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="state">State/Province</Label>
                    <Input
                      id="state"
                      value={formData.state}
                      onChange={(e) => setFormData({ ...formData, state: e.target.value })}
                      placeholder="Metro Manila"
                    />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="grid gap-2">
                    <Label htmlFor="postal_code">Postal Code</Label>
                    <Input
                      id="postal_code"
                      value={formData.postal_code}
                      onChange={(e) => setFormData({ ...formData, postal_code: e.target.value })}
                      placeholder="1000"
                    />
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="phone">Phone</Label>
                    <Input
                      id="phone"
                      value={formData.phone}
                      onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                      placeholder="+63 123 456 7890"
                    />
                  </div>
                </div>
              </div>
              <DialogFooter>
                <Button type="submit" disabled={createMutation.isPending}>
                  {createMutation.isPending ? 'Creating...' : 'Create Warehouse'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {isLoading ? (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {[...Array(3)].map((_, i) => (
            <Card key={i}>
              <CardHeader>
                <div className="h-6 w-32 animate-pulse rounded bg-muted" />
              </CardHeader>
              <CardContent>
                <div className="h-4 w-48 animate-pulse rounded bg-muted" />
              </CardContent>
            </Card>
          ))}
        </div>
      ) : data?.data?.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-10">
            <WarehouseIcon className="h-12 w-12 text-muted-foreground" />
            <p className="mt-4 text-lg font-medium">No warehouses yet</p>
            <p className="text-sm text-muted-foreground">Add your first warehouse to get started</p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {data?.data?.map((warehouse) => (
            <Card key={warehouse.id}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-lg font-semibold">{warehouse.name}</CardTitle>
                <div className="flex gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => handleEditClick(warehouse)}
                  >
                    <Pencil className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => deleteMutation.mutate(warehouse.id)}
                    disabled={deleteMutation.isPending}
                  >
                    <Trash2 className="h-4 w-4 text-destructive" />
                  </Button>
                </div>
              </CardHeader>
              <CardContent>
                <div className="flex items-start gap-2 text-sm text-muted-foreground">
                  <MapPin className="mt-0.5 h-4 w-4 shrink-0" />
                  <div>
                    <p>{warehouse.address}</p>
                    {(warehouse.city || warehouse.state) && (
                      <p>
                        {warehouse.city}
                        {warehouse.city && warehouse.state && ', '}
                        {warehouse.state} {warehouse.postal_code}
                      </p>
                    )}
                    {warehouse.phone && <p className="mt-1">{warehouse.phone}</p>}
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Edit Warehouse Dialog */}
      <Dialog open={editOpen} onOpenChange={(open) => {
        setEditOpen(open);
        if (!open) setEditingWarehouse(null);
      }}>
        <DialogContent className="max-w-md">
          <form onSubmit={handleEditSubmit}>
            <DialogHeader>
              <DialogTitle>Edit Warehouse</DialogTitle>
              <DialogDescription>Update warehouse details</DialogDescription>
            </DialogHeader>
            <div className="grid gap-4 py-4">
              <div className="grid gap-2">
                <Label htmlFor="edit_name">Warehouse Name</Label>
                <Input
                  id="edit_name"
                  value={editFormData.name}
                  onChange={(e) => setEditFormData({ ...editFormData, name: e.target.value })}
                  placeholder="Main Warehouse"
                  required
                />
              </div>
              <div className="grid gap-2">
                <Label htmlFor="edit_address">Address</Label>
                <Input
                  id="edit_address"
                  value={editFormData.address}
                  onChange={(e) => setEditFormData({ ...editFormData, address: e.target.value })}
                  placeholder="123 Storage St"
                  required
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                  <Label htmlFor="edit_city">City</Label>
                  <Input
                    id="edit_city"
                    value={editFormData.city}
                    onChange={(e) => setEditFormData({ ...editFormData, city: e.target.value })}
                    placeholder="Manila"
                  />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="edit_state">State/Province</Label>
                  <Input
                    id="edit_state"
                    value={editFormData.state}
                    onChange={(e) => setEditFormData({ ...editFormData, state: e.target.value })}
                    placeholder="Metro Manila"
                  />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                  <Label htmlFor="edit_postal_code">Postal Code</Label>
                  <Input
                    id="edit_postal_code"
                    value={editFormData.postal_code}
                    onChange={(e) => setEditFormData({ ...editFormData, postal_code: e.target.value })}
                    placeholder="1000"
                  />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="edit_phone">Phone</Label>
                  <Input
                    id="edit_phone"
                    value={editFormData.phone}
                    onChange={(e) => setEditFormData({ ...editFormData, phone: e.target.value })}
                    placeholder="+63 123 456 7890"
                  />
                </div>
              </div>
            </div>
            <DialogFooter>
              <Button type="submit" disabled={updateMutation.isPending}>
                {updateMutation.isPending ? 'Saving...' : 'Save Changes'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
