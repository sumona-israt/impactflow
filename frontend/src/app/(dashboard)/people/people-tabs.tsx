"use client";

import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PaginationControls } from "@/components/shared/pagination-controls";
import { listEmployees, listVolunteers } from "@/lib/api/endpoints/staff";
import { statusLabel } from "@/lib/format";
import { CreateEmployeeDialog } from "./create-employee-dialog";
import { CreateVolunteerDialog } from "./create-volunteer-dialog";

export function PeopleTabs({
  canViewEmployees,
  canCreateEmployee,
  canViewVolunteers,
  canCreateVolunteer,
}: {
  canViewEmployees: boolean;
  canCreateEmployee: boolean;
  canViewVolunteers: boolean;
  canCreateVolunteer: boolean;
}) {
  const [employeePage, setEmployeePage] = useState(1);
  const [volunteerPage, setVolunteerPage] = useState(1);

  const employeesQuery = useQuery({
    queryKey: ["employees", employeePage],
    queryFn: () => listEmployees({ page: employeePage }),
    enabled: canViewEmployees,
  });
  const volunteersQuery = useQuery({
    queryKey: ["volunteers", volunteerPage],
    queryFn: () => listVolunteers({ page: volunteerPage }),
    enabled: canViewVolunteers,
  });

  const defaultTab = canViewEmployees ? "employees" : "volunteers";

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-lg font-semibold tracking-tight">Staff &amp; Volunteers</h1>
        <p className="text-sm text-muted-foreground">Employee and volunteer records.</p>
      </div>

      <Tabs defaultValue={defaultTab}>
        <TabsList>
          {canViewEmployees && <TabsTrigger value="employees">Employees</TabsTrigger>}
          {canViewVolunteers && <TabsTrigger value="volunteers">Volunteers</TabsTrigger>}
        </TabsList>

        {canViewEmployees && (
          <TabsContent value="employees" className="space-y-3 pt-4">
            {canCreateEmployee && <CreateEmployeeDialog />}
            <div className="rounded-lg border bg-card">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Position</TableHead>
                    <TableHead>Department</TableHead>
                    <TableHead>Branch</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {employeesQuery.isLoading &&
                    Array.from({ length: 4 }).map((_, i) => (
                      <TableRow key={i}><TableCell colSpan={5}><Skeleton className="h-6 w-full" /></TableCell></TableRow>
                    ))}
                  {employeesQuery.data?.data.length === 0 && (
                    <TableRow><TableCell colSpan={5} className="py-6 text-center text-sm text-muted-foreground">No employees yet.</TableCell></TableRow>
                  )}
                  {employeesQuery.data?.data.map((employee) => (
                    <TableRow key={employee.id}>
                      <TableCell className="font-medium">{employee.name}</TableCell>
                      <TableCell className="text-muted-foreground">{employee.position ?? "—"}</TableCell>
                      <TableCell className="text-muted-foreground">{employee.department?.name ?? "—"}</TableCell>
                      <TableCell className="text-muted-foreground">{employee.branch?.name ?? "—"}</TableCell>
                      <TableCell><Badge variant="secondary">{statusLabel(employee.status)}</Badge></TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            {employeesQuery.data && <PaginationControls meta={employeesQuery.data.meta} onPageChange={setEmployeePage} />}
          </TabsContent>
        )}

        {canViewVolunteers && (
          <TabsContent value="volunteers" className="space-y-3 pt-4">
            {canCreateVolunteer && <CreateVolunteerDialog />}
            <div className="rounded-lg border bg-card">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Phone</TableHead>
                    <TableHead>Skills</TableHead>
                    <TableHead>Availability</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {volunteersQuery.isLoading &&
                    Array.from({ length: 4 }).map((_, i) => (
                      <TableRow key={i}><TableCell colSpan={5}><Skeleton className="h-6 w-full" /></TableCell></TableRow>
                    ))}
                  {volunteersQuery.data?.data.length === 0 && (
                    <TableRow><TableCell colSpan={5} className="py-6 text-center text-sm text-muted-foreground">No volunteers yet.</TableCell></TableRow>
                  )}
                  {volunteersQuery.data?.data.map((volunteer) => (
                    <TableRow key={volunteer.id}>
                      <TableCell className="font-medium">{volunteer.full_name}</TableCell>
                      <TableCell className="text-muted-foreground">{volunteer.phone ?? "—"}</TableCell>
                      <TableCell className="text-muted-foreground">{volunteer.skills.join(", ") || "—"}</TableCell>
                      <TableCell className="text-muted-foreground">{volunteer.availability ? statusLabel(volunteer.availability) : "—"}</TableCell>
                      <TableCell><Badge variant="secondary">{statusLabel(volunteer.status)}</Badge></TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            {volunteersQuery.data && <PaginationControls meta={volunteersQuery.data.meta} onPageChange={setVolunteerPage} />}
          </TabsContent>
        )}
      </Tabs>
    </div>
  );
}
