<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:SuperAdministrateur|Administrateur']);
    }

    public function index()
    {
        $ecoleId = session('current_ecole_id');
        
        $templates = DocumentTemplate::where('ecole_id', $ecoleId)
            ->orderBy('type')
            ->orderBy('nom')
            ->get();

        $types = DocumentTemplate::getTypes();

        return view('dashboard.pages.comptabilites.templates.index', compact('templates', 'types'));
    }

    public function create(Request $request)
    {
        $type = $request->get('type', 'recu_paiement');
        $variables = DocumentTemplate::getVariablesByType($type);
        $types = DocumentTemplate::getTypes();
        
        $defaultContent = $this->getDefaultContent($type);

        return view('dashboard.pages.comptabilites.templates.create', compact(
            'type', 
            'variables', 
            'types', 
            'defaultContent'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|string|in:recu_paiement,relance,information,bulletin,autre',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'is_default' => 'nullable|boolean'
        ]);

        $ecoleId = session('current_ecole_id');

        try {
            DB::beginTransaction();

            if ($request->is_default) {
                DocumentTemplate::where('ecole_id', $ecoleId)
                    ->where('type', $request->type)
                    ->update(['is_default' => false]);
            }

            $count = DocumentTemplate::where('ecole_id', $ecoleId)
                ->where('type', $request->type)
                ->count();

            $template = DocumentTemplate::create([
                'ecole_id' => $ecoleId,
                'nom' => $request->nom,
                'type' => $request->type,
                'content' => $request->content,
                'variables' => DocumentTemplate::getVariablesByType($request->type),
                'is_default' => $request->is_default ?? ($count === 0),
                'is_active' => true,
                'description' => $request->description
            ]);

            DB::commit();

            return redirect()->route('templates.index')
                ->with('success', 'Modèle créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création template: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la création du modèle.');
        }
    }

    public function edit($id)
    {
        $ecoleId = session('current_ecole_id');
        
        $template = DocumentTemplate::where('ecole_id', $ecoleId)
            ->findOrFail($id);

        $variables = DocumentTemplate::getVariablesByType($template->type);
        $types = DocumentTemplate::getTypes();

        return view('dashboard.pages.comptabilites.templates.edit', compact('template', 'variables', 'types'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|string|in:recu_paiement,relance,information,bulletin,autre',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ]);

        $ecoleId = session('current_ecole_id');

        try {
            DB::beginTransaction();

            $template = DocumentTemplate::where('ecole_id', $ecoleId)
                ->findOrFail($id);

            if ($request->is_default) {
                DocumentTemplate::where('ecole_id', $ecoleId)
                    ->where('type', $template->type)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $template->update([
                'nom' => $request->nom,
                'type' => $request->type,
                'content' => $request->content,
                'description' => $request->description,
                'is_default' => $request->is_default ?? false,
                'is_active' => $request->is_active ?? true,
            ]);

            DB::commit();

            return redirect()->route('templates.index')
                ->with('success', 'Modèle mis à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour template: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la mise à jour du modèle.');
        }
    }

    public function destroy($id)
    {
        $ecoleId = session('current_ecole_id');

        try {
            $template = DocumentTemplate::where('ecole_id', $ecoleId)
                ->findOrFail($id);

            $count = DocumentTemplate::where('ecole_id', $ecoleId)
                ->where('type', $template->type)
                ->count();

            if ($count <= 1) {
                return back()->with('error', 'Impossible de supprimer le seul modèle de ce type.');
            }

            $template->delete();

            return redirect()->route('templates.index')
                ->with('success', 'Modèle supprimé avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur suppression template: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la suppression du modèle.');
        }
    }

    public function show($id)
    {
        $ecoleId = session('current_ecole_id');
        
        $template = DocumentTemplate::where('ecole_id', $ecoleId)
            ->findOrFail($id);

        $variables = DocumentTemplate::getVariablesByType($template->type);

        return view('dashboard.pages.comptabilites.templates.show', compact('template', 'variables'));
    }

    /**
     * Contenu par défaut pour chaque type
     */
    private function getDefaultContent($type)
    {
        return match($type) {
            'recu_paiement' => $this->getDefaultRecuContent(),
            'relance' => $this->getDefaultRelanceContent(),
            'information' => $this->getDefaultInformationContent(),
            'bulletin' => $this->getDefaultBulletinContent(),
            default => '<p>Contenu du modèle personnalisé...</p>',
        };
    }

    private function getDefaultRecuContent()
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd;">
            <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px;">
                <h1 style="margin: 0;">%ECOLE%</h1>
                <p>%ECOLE_ADRESSE%</p>
                <h2>REÇU DE PAIEMENT</h2>
                <p>N° %NUMERO_RECU% | Date: %DATE%</p>
            </div>
            <div style="padding: 20px 0;">
                <p><strong>Élève :</strong> %NOM% %PRENOM%</p>
                <p><strong>Classe :</strong> %CLASSE%</p>
                <p><strong>Matricule :</strong> %MATRICULE%</p>
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #f5f5f5;">
                    <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Désignation</th>
                    <th style="border: 1px solid #ddd; padding: 10px; text-align: right;">Montant</th>
                </tr>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 10px;">%TYPE_FRAIS%</td>
                    <td style="border: 1px solid #ddd; padding: 10px; text-align: right;">%MONTANT% FCFA</td>
                </tr>
                <tr style="font-weight: bold;">
                    <td style="border: 1px solid #ddd; padding: 10px;">Total payé</td>
                    <td style="border: 1px solid #ddd; padding: 10px; text-align: right;">%MONTANT% FCFA</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 10px;">Reste à payer</td>
                    <td style="border: 1px solid #ddd; padding: 10px; text-align: right;">%RESTE% FCFA</td>
                </tr>
            </table>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center;">
                <p>Arrêté le présent reçu à la somme de <strong>%MONTANT% FCFA</strong></p>
                <p>Soit en lettres : %MONTANT_LETTRES% Francs CFA</p>
                <p>Mode de paiement : %MODE_PAIEMENT%</p>
                <p>Référence : %REFERENCE%</p>
            </div>
        </div>
        ';
    }

    private function getDefaultRelanceContent()
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px;">
                <h1 style="margin: 0;">%ECOLE%</h1>
                <p>%ECOLE_ADRESSE%</p>
            </div>
            <div style="padding: 20px 0;">
                <p style="text-align: right;">Le %DATE%</p>
                <p><strong>Objet :</strong> Relance de paiement n°%NOMBRE_RELANCE%</p>
            </div>
            <div style="margin: 20px 0;">
                <p><strong>Parent de :</strong> %NOM% %PRENOM%</p>
                <p>Élève en classe de %CLASSE%</p>
            </div>
            <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;">
                <p><strong>Rappel :</strong></p>
                <p>Arriéré de paiement de <strong>%MONTANT_DU% FCFA</strong></p>
                <p>Date d\'échéance : %DATE_ECHEANCE%</p>
                <p>Retard : %RETARD% jours</p>
            </div>
            <div style="margin: 20px 0;">
                <p>Nous vous prions de régulariser dans un délai de %DELAI%.</p>
                <p>Passé ce délai, %SANCTION%.</p>
            </div>
            <div style="text-align: center; border-top: 1px solid #ddd; padding-top: 20px;">
                <p><strong>La Direction</strong></p>
            </div>
        </div>
        ';
    }

    private function getDefaultInformationContent()
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px;">
                <h1 style="margin: 0;">%ECOLE%</h1>
                <p>%ECOLE_ADRESSE%</p>
            </div>
            <div style="padding: 20px 0;">
                <p style="text-align: right;">Le %DATE%</p>
                <p><strong>Objet :</strong> %OBJET%</p>
            </div>
            <div style="margin: 20px 0;">
                <p>Madame, Monsieur,</p>
                <p>%DETAIL%</p>
                <p><strong>Événement :</strong> %EVENEMENT%</p>
                <p><strong>Date :</strong> %DATE_EVENEMENT%</p>
                <p><strong>Heure :</strong> %HEURE%</p>
                <p><strong>Lieu :</strong> %LIEU%</p>
            </div>
            <div style="text-align: center; border-top: 1px solid #ddd; padding-top: 20px;">
                <p>Cordialement,</p>
                <p><strong>La Direction</strong></p>
            </div>
        </div>
        ';
    }

    private function getDefaultBulletinContent()
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px;">
                <h1 style="margin: 0;">%ECOLE%</h1>
                <p>%ECOLE_ADRESSE%</p>
                <h2>BULLETIN DE NOTES</h2>
                <p>Année scolaire : %ANNEE%</p>
            </div>
            <div style="padding: 20px 0;">
                <p><strong>Élève :</strong> %NOM% %PRENOM%</p>
                <p><strong>Classe :</strong> %CLASSE%</p>
                <p><strong>Matricule :</strong> %MATRICULE%</p>
            </div>
            <div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p><strong>Résultats :</strong></p>
                <p>Moyenne générale : %MOYENNE%</p>
                <p>Rang : %RANG% / %EFFECTIF%</p>
                <p>Appréciation : %APPRECIATION%</p>
            </div>
            <div style="text-align: center; border-top: 1px solid #ddd; padding-top: 20px;">
                <p><strong>La Direction</strong></p>
            </div>
        </div>
        ';
    }

    /**
 * Récupérer les modèles SMS actifs
 */
public function getActiveSms()
{
    $ecoleId = session('current_ecole_id');
    
    $templates = DocumentTemplate::where('ecole_id', $ecoleId)
        ->where('is_active', true)
        ->whereIn('type', ['relance', 'recu_paiement', 'information'])
        ->orderBy('is_default', 'desc')
        ->orderBy('nom')
        ->get()
        ->map(function($template) {
            return [
                'id' => $template->id,
                'nom' => $template->nom,
                'content' => $template->content,
                'type' => $template->type,
                'type_label' => DocumentTemplate::getTypes()[$template->type] ?? $template->type,
                'is_default' => $template->is_default,
            ];
        });
    
    return response()->json([
        'success' => true,
        'data' => $templates
    ]);
}
}