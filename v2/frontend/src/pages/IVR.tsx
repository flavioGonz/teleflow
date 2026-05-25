import { useCallback, useEffect, useState } from 'react';
import {
  ReactFlow, Background, Controls, MiniMap, useNodesState, useEdgesState, addEdge,
  type Node, type Edge, type OnConnect, type NodeChange, type EdgeChange,
} from 'reactflow';
import 'reactflow/dist/style.css';
import { Workflow, Loader2, Save, AlertCircle, Plus } from 'lucide-react';
import { api } from '../lib/api';
import { Card, CardContent } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';

interface IvrFlowData {
  nodes: Node[];
  edges: Edge[];
}

const EMPTY_FLOW: IvrFlowData = {
  nodes: [
    {
      id: 'entry',
      type: 'input',
      position: { x: 250, y: 50 },
      data: { label: '📞 Entrada' },
    },
  ],
  edges: [],
};

export default function IVR() {
  const [flow, setFlow] = useState<IvrFlowData>(EMPTY_FLOW);
  const [details, setDetails] = useState<any[]>([]);
  const [asteriskDown, setAsteriskDown] = useState(false);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);

  const [nodes, setNodes, onNodesChange] = useNodesState(flow.nodes);
  const [edges, setEdges, onEdgesChange] = useEdgesState(flow.edges);

  useEffect(() => {
    api.get('/ivr')
      .then((r) => {
        if (r.data.flow && r.data.flow.nodes) {
          setFlow(r.data.flow);
          setNodes(r.data.flow.nodes);
          setEdges(r.data.flow.edges || []);
        }
        setDetails(r.data.details || []);
      })
      .catch((e) => {
        if (e.response?.data?.code === 'ASTERISK_UNAVAILABLE') setAsteriskDown(true);
      })
      .finally(() => setLoading(false));
  }, [setNodes, setEdges]);

  const onConnect: OnConnect = useCallback((params) => setEdges((eds) => addEdge(params, eds)), [setEdges]);

  async function save() {
    setSaving(true);
    try {
      await api.put('/ivr/flow', { nodes, edges });
      setSaved(true);
      setTimeout(() => setSaved(false), 2500);
    } catch (e: any) {
      alert('Error: ' + (e.response?.data?.error || e.message));
    } finally {
      setSaving(false);
    }
  }

  function addMenuNode() {
    const id = `menu-${Date.now()}`;
    setNodes((nds) => [
      ...nds,
      {
        id,
        position: { x: 250, y: 200 + nds.length * 80 },
        data: { label: '📋 Menú IVR' },
        style: { background: 'hsl(var(--primary))', color: 'white', border: 'none', borderRadius: 8, padding: 10 },
      },
    ]);
  }

  function addExtNode() {
    const id = `ext-${Date.now()}`;
    setNodes((nds) => [
      ...nds,
      {
        id,
        position: { x: 450, y: 200 + nds.length * 80 },
        data: { label: '☎️ Extensión' },
      },
    ]);
  }

  function addQueueNode() {
    const id = `queue-${Date.now()}`;
    setNodes((nds) => [
      ...nds,
      {
        id,
        position: { x: 50, y: 200 + nds.length * 80 },
        data: { label: '👥 Cola' },
        style: { background: '#3b82f6', color: 'white', border: 'none', borderRadius: 8, padding: 10 },
      },
    ]);
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
            <Workflow className="h-6 w-6 text-primary" />
            IVR — Flow Designer
          </h1>
          <p className="text-sm text-muted-foreground mt-1">
            Diseñá visualmente el menú de voz. {details.length > 0 && `${details.length} IVRs en FreePBX.`}
          </p>
        </div>
        <div className="flex gap-2">
          {saved && <span className="text-xs text-green-500 font-semibold self-center">✓ Guardado</span>}
          <Button variant="outline" onClick={addQueueNode}><Plus className="h-3 w-3" />Cola</Button>
          <Button variant="outline" onClick={addExtNode}><Plus className="h-3 w-3" />Extensión</Button>
          <Button variant="outline" onClick={addMenuNode}><Plus className="h-3 w-3" />Menú</Button>
          <Button onClick={save} disabled={saving}>
            {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
            Guardar flow
          </Button>
        </div>
      </div>

      {asteriskDown && (
        <Card className="border-amber-500/40 bg-amber-500/5">
          <CardContent className="p-4 flex items-center gap-3">
            <AlertCircle className="h-5 w-5 text-amber-500" />
            <div className="text-sm">
              <strong>Asterisk no disponible</strong> — podés editar el flow igual (se guarda en Postgres),
              pero no leerás los IVRs existentes del FreePBX.
            </div>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardContent className="p-0">
          <div style={{ width: '100%', height: '70vh' }}>
            {loading ? (
              <div className="h-full flex items-center justify-center text-muted-foreground gap-2">
                <Loader2 className="h-4 w-4 animate-spin" />
                Cargando...
              </div>
            ) : (
              <ReactFlow
                nodes={nodes}
                edges={edges}
                onNodesChange={onNodesChange}
                onEdgesChange={onEdgesChange}
                onConnect={onConnect}
                fitView
              >
                <Background gap={16} />
                <Controls />
                <MiniMap />
              </ReactFlow>
            )}
          </div>
        </CardContent>
      </Card>

      {details.length > 0 && (
        <Card>
          <CardContent className="p-5">
            <h3 className="font-semibold mb-3">IVRs existentes en FreePBX</h3>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
              {details.map((d: any) => (
                <div key={d.id} className="px-3 py-2 rounded-md border bg-card text-sm">
                  <div className="font-semibold">{d.displayname}</div>
                  <div className="text-xs text-muted-foreground font-mono">ID {d.id}</div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
