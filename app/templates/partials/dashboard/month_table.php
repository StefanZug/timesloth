<div class="widget-card h-100">
    <div class="widget-header">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary border-0 py-0" @click="shiftMonth(-1)"><i class="bi bi-chevron-left"></i></button>
            <span>🗓️ [[ displayMonthName ]]</span>
            <button class="btn btn-sm btn-outline-secondary border-0 py-0" @click="shiftMonth(1)"><i class="bi bi-chevron-right"></i></button>
        </div>
        <button class="btn btn-sm btn-link text-danger p-0" @click="resetMonth"><i class="bi bi-trash"></i></button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-compact align-middle mb-0 mobile-dashboard-table" style="font-size: 0.9rem;">
            <thead class="bg-body-tertiary">
                <tr>
                    <th class="ps-3 col-min-date">Datum</th>
                    <th class="text-center ps-4 col-min-time">Zeiten</th>
                    <th class="text-center">SAP</th>
                    <th class="text-center">Status</th>
                    <th class="col-min-note">Notiz</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="day in monthDays" :key="day.iso" :class="getRowClass(day)">
                    <td class="ps-3 cursor-pointer text-nowrap" @click="jumpToDay(day.iso)">
                        <div class="fw-bold" :class="day.isToday ? 'text-primary' : 'text-body'">[[ day.dayShort ]] [[ day.dateNum ]].</div>
                        <div class="text-subtle">KW [[ day.kw ]]</div>
                    </td>
                    <td>
                        <div v-if="!day.status">
                            <div v-for="(block, index) in day.blocks" :key="block.id" class="d-flex align-items-center gap-1 mb-2">
                                <div class="dropdown d-inline-block list-icon-btn">
                                    <button class="btn btn-sm p-0 border-0 w-100" type="button" data-bs-toggle="dropdown">
                                        <i class="bi" :class="getTypeIcon(block.type)" :style="{color: block.type === 'office' ? 'var(--sloth-primary)' : 'inherit'}" style="font-size: 1rem;"></i>
                                    </button>
                                    <ul class="dropdown-menu shadow">
                                        <li><button type="button" class="dropdown-item" @click="changeListBlockType($event, day, index, 'office')"><i class="bi bi-building me-2 text-success"></i>Büro</button></li>
                                        <li><button type="button" class="dropdown-item" @click="changeListBlockType($event, day, index, 'home')"><i class="bi bi-house me-2 text-info"></i>Home</button></li>
                                        <li><button type="button" class="dropdown-item text-danger" @click="changeListBlockType($event, day, index, 'doctor')"><i class="bi bi-bandaid me-2"></i>Arzt</button></li>
                                    </ul>
                                </div>
                                <input :type="inputType" :step="getStep(block)" class="table-input" v-model="block.start" @blur="formatListTime(day, index, 'start')" @input="triggerListSave(day)" @wheel.prevent="onWheel($event, block, 'start', day)">
                                <span class="text-muted text-xs">-</span>
                                <input :type="inputType" :step="getStep(block)" class="table-input" v-model="block.end" @blur="formatListTime(day, index, 'end')" @input="triggerListSave(day)" @wheel.prevent="onWheel($event, block, 'end', day)">
                                <span class="duration-badge" :style="{ visibility: getBlockDuration(block) ? 'visible' : 'hidden' }">
                                    [[ getBlockDuration(block) || '0,00' ]]
                                </span>
                                <button class="btn btn-link text-danger list-remove-btn" @click="removeListBlock(day, index)"><i class="bi bi-x"></i></button>
                            </div>
                            <div class="d-flex gap-1 mt-1">
                                <div class="list-spacer-start"></div>
                                <button class="btn btn-dashed btn-sm flex-fill py-1" @click="addListBlock(day, 'office')"><i class="bi bi-plus-lg"></i></button>
                                <div class="list-spacer-end"></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center fw-bold cursor-pointer" @click="jumpToDay(day.iso)">[[ day.sapTime > 0 ? formatH(day.sapTime) : '-' ]]</td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <div class="btn-xs-status btn-status-sm st-f" :class="{active: day.status === 'F'}" @click.stop="quickToggle(day, 'F')">F</div>
                            <div class="btn-xs-status btn-status-sm st-u" :class="{active: day.status === 'U'}" @click.stop="quickToggle(day, 'U')">U</div>
                            <div class="btn-xs-status btn-status-sm st-k" :class="{active: day.status === 'K'}" @click.stop="quickToggle(day, 'K')">K</div>
                        </div>
                    </td>
                    
                    <td>
                        <div v-if="expandedNoteIso !== day.iso" class="d-flex align-items-center gap-2">
                            
                            <div v-if="day.comment" 
                                    class="flex-grow-1 text-truncate cursor-pointer markdown-preview note-preview-closed" 
                                    style="height: 28px; overflow: hidden; line-height: 1.5;"
                                    @click="toggleExpandNote(day)"
                                    :title="day.comment" 
                                    v-html="renderMarkdown(day.comment)">
                            </div>
                            
                            <input v-else 
                                    type="text" 
                                    class="form-control form-control-sm table-input-note text-truncate" 
                                    v-model.lazy="day.comment" 
                                    :placeholder="day.placeholder" 
                                    @change="updateComment(day)">

                            <button class="btn btn-sm btn-link text-muted p-0" @click="toggleExpandNote(day)" title="Bearbeiten">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </div>

                        <div v-else class="note-popup bg-card shadow p-2 rounded border border-primary">
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-light border" @click="insertMarkdown('bold', $event)"><i class="bi bi-type-bold"></i></button>
                                    <button class="btn btn-light border" @click="insertMarkdown('list', $event)"><i class="bi bi-list-ul"></i></button>
                                </div>
                                <button class="btn btn-sm btn-link text-muted p-0" @click="toggleExpandNote(day)" title="Zuklappen">
                                    <i class="bi bi-arrows-collapse"></i>
                                </button>
                            </div>

                            <textarea class="form-control form-control-sm font-monospace" 
                                        rows="4" 
                                        v-model.lazy="day.comment" 
                                        @change="updateComment(day)"
                                        placeholder="Notiz...">
                            </textarea>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>